pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'mramoscli/laravel-mysql'
        BRANCH       = "${env.GIT_BRANCH}".replaceAll('origin/', '')
    }

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    stages {

        stage('Preparar entorno Laravel') {
            agent {
                docker {
                    image 'laravelsail/php82-composer:latest'
                    args  '-u root'
                }
            }
            steps {
                sh '''
                    echo "🔧 Preparando entorno Laravel…"
                    cp .env.example .env
                    composer install
                    php artisan key:generate
                '''
            }
        }

        stage('Pruebas y Cobertura') {
            agent {
                docker {
                    image 'laravelsail/php82-composer:latest'
                    args  '-u root'
                }
            }
            steps {
                sh '''
                    echo "🧪 Ejecutando pruebas con SQLite en memoria…"

                    apt-get update -qq
                    apt-get install -y sqlite3 libsqlite3-dev git unzip curl pkg-config libzip-dev build-essential autoconf

                    yes "" | pecl install xdebug
                    docker-php-ext-enable xdebug
                    printf "xdebug.mode=coverage\\n" > /usr/local/etc/php/conf.d/xdebug.ini

                    docker-php-ext-install pdo pdo_sqlite

                    composer install
                    cp .env.testing .env
                    php artisan config:clear
                    php artisan migrate --seed

                    mkdir -p storage/logs
                    php artisan test \
                        --log-junit=storage/logs/junit.xml \
                        --coverage-cobertura=coverage.xml
                '''

                junit allowEmptyResults: true, testResults: 'storage/logs/junit.xml'
                archiveArtifacts artifacts: 'storage/logs/*.xml', fingerprint: true
            }
        }

        stage('Build Docker') {
            steps {
                script {
                    def tag = BRANCH == 'main' ? 'prod' :
                              BRANCH == 'develop' ? 'dev' : 'test'
                    env.TAG = tag
                }
                sh 'docker build -t $DOCKER_IMAGE:$TAG .'
            }
        }

        stage('Push a Docker Hub') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'docker-hub-creds',
                                                  usernameVariable: 'DOCKER_USER',
                                                  passwordVariable: 'DOCKER_PASS')]) {
                    sh '''
                        echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
                        docker push $DOCKER_IMAGE:$TAG
                    '''
                }
            }
        }

        stage('Desplegar en Servidor de App (VM2)') {
                    steps {
                        sshagent (credentials: ['vm2-ssh']) {
                            sh '''
                                ssh -o StrictHostKeyChecking=no miguel@192.168.1.94 "
                                    echo '🔄 Actualizando imagen y ejecutando contenedor…'
                                    docker pull $DOCKER_IMAGE:$TAG
                                    cd /home/miguel/docker-compose
                                    TAG=$TAG docker compose \
                                        -f docker-compose.yml \
                                        -f docker-compose.$TAG.yml \
                                        up -d --remove-orphans
                                "
                            '''
                        }
                    }
                }

        stage('Migraciones en Producción') {
            when {
                expression { env.TAG == 'prod' }
            }
            steps {
                sshagent (credentials: ['vm2-ssh']) {
                    sh '''
                        ssh -o StrictHostKeyChecking=no miguel@192.168.1.94 '
                            echo "📦 Ejecutando migraciones en producción"
                            docker exec docker-compose-app-1 php artisan migrate --force
                        '
                    '''
                }
            }
        }
    }

    // post {
    //     always {
    //         echo '📦 Archivando cobertura y artefactos globales…'
    //         archiveArtifacts artifacts: 'storage/logs/coverage.xml', fingerprint: true
    //         recordCoverage tools: [
    //             [parser: 'COBERTURA', pattern: 'storage/logs/coverage.xml']
    //         ], sourceCodeRetention: 'NEVER'
    //     }
    // }
}
