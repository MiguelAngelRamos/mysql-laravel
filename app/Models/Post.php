<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory; // 👈 Asegúrate de que esto esté presente
    // ⬅️ Aquí agregamos los campos permitidos para asignación masiva
    protected $fillable = ['title', 'content'];
}
