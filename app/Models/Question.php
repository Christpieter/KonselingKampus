<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['position', 'text', 'subscale'];

    protected $casts = [
        'subscale' => 'string',
    ];

    public const SUBSCALES = [
        'depression' => 'Depresi',
        'anxiety' => 'Kecemasan',
        'stress' => 'Stres',
    ];

    public const TRIAGE_QUESTIONS = [
        // Anxiety items (3 items)
        ['position' => 1, 'text' => 'Saya merasa gelisah atau kegugupan yang sulit diatasi.', 'subscale' => 'anxiety'],
        ['position' => 2, 'text' => 'Saya merasa jantung berdebar-debar atau napas terasa sesak tanpa alasan fisik yang jelas.', 'subscale' => 'anxiety'],
        ['position' => 3, 'text' => 'Saya merasa takut atau cemas tanpa alasan yang jelas.', 'subscale' => 'anxiety'],

        // Depression items (3 items)
        ['position' => 4, 'text' => 'Saya merasa tidak memiliki semangat atau minat untuk hal-hal yang biasanya saya nikmati.', 'subscale' => 'depression'],
        ['position' => 5, 'text' => 'Saya merasa putus asa atau tidak berharga.', 'subscale' => 'depression'],
        ['position' => 6, 'text' => 'Saya merasa sulit memulai atau menyelesaikan aktivitas sehari-hari.', 'subscale' => 'depression'],

        // Stress items (4 items)
        ['position' => 7, 'text' => 'Saya merasa sulit untuk tenang atau relaks.', 'subscale' => 'stress'],
        ['position' => 8, 'text' => 'Saya merasa mudah tersinggung atau marah.', 'subscale' => 'stress'],
        ['position' => 9, 'text' => 'Saya merasa tertekan atau kewalahan oleh situasi.', 'subscale' => 'stress'],
        ['position' => 10, 'text' => 'Saya merasa sulit tidur atau istirahat dengan nyenyak.', 'subscale' => 'stress'],
    ];
}
