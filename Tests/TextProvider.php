<?php
/**
 * Copyright (C) 2014, Some right reserved.
 * @author  Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

namespace Kadet\SocketLib\Tests;


trait TextProvider
{
    public function asciiProvider()
    {
        return [
            'short'          => ['text'],
            'long'           => ['jGQLVcIgnyN0y6r8o3j0butvmZPj6CLE4Wi1ymXIA1rbG2Kz4Uuv3CvAgjbwVnjrJdmGFpNPsO4ObjuPvQlCBqnugUgBifRIQmXVxYTJXyg4XErifJ4CGWtB'],
            'extremely long' => [str_repeat('djkgnepsdfs', 1024)],
        ];
    }

    public function utf8Provider()
    {
        return [
            'rune'           => ['ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ'],
            'middle english' => ['He wonede at Ernleȝe at æðelen are chirechen'],
            'greek'          => ['τὸ σπίτι φτωχικὸ στὶς ἀμμουδιὲς τοῦ Ὁμήρου.'],
            'bad'            => ['�����������������������������'],
            'cyrillic'       => ['И вдаль глядел. Пред ним широко'],
            'Georgian'       => ['ვეპხის ტყაოსანი შოთა რუსთაველი'],
            'tamil'          => ['யாமறிந்த மொழிகளிலே தமிழ்மொழி போல்'],
            'polish'         => ['Mogę jeść szkło i mi nie szkodzi.']
        ];
    }
} 