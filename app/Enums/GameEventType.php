<?php

namespace App\Enums;

enum GameEventType: string
{
    use HasOptions;

    case Point1 = 'point_1';
    case Point2 = 'point_2';
    case Point3 = 'point_3';
    case Miss1 = 'miss_1';
    case Miss2 = 'miss_2';
    case Miss3 = 'miss_3';
    case ReboundOff = 'rebound_off';
    case ReboundDef = 'rebound_def';
    case Assist = 'assist';
    case Steal = 'steal';
    case Block = 'block';
    case Turnover = 'turnover';
    case FoulPersonal = 'foul_personal';
    case FoulTechnical = 'foul_technical';
    case FoulUnsportsmanlike = 'foul_unsportsmanlike';
    case FoulDisqualifying = 'foul_disqualifying';
    case SubIn = 'sub_in';
    case SubOut = 'sub_out';
    case Timeout = 'timeout';
    case PeriodStart = 'period_start';
    case PeriodEnd = 'period_end';

    public function label(): string
    {
        return match ($this) {
            self::Point1 => 'Tiro libre anotado',
            self::Point2 => 'Canasta de 2',
            self::Point3 => 'Canasta de 3',
            self::Miss1 => 'Tiro libre fallado',
            self::Miss2 => 'Tiro de 2 fallado',
            self::Miss3 => 'Tiro de 3 fallado',
            self::ReboundOff => 'Rebote ofensivo',
            self::ReboundDef => 'Rebote defensivo',
            self::Assist => 'Asistencia',
            self::Steal => 'Robo',
            self::Block => 'Bloqueo',
            self::Turnover => 'Pérdida',
            self::FoulPersonal => 'Falta personal',
            self::FoulTechnical => 'Falta técnica',
            self::FoulUnsportsmanlike => 'Falta antideportiva',
            self::FoulDisqualifying => 'Falta descalificante',
            self::SubIn => 'Entra',
            self::SubOut => 'Sale',
            self::Timeout => 'Tiempo muerto',
            self::PeriodStart => 'Inicio de período',
            self::PeriodEnd => 'Fin de período',
        };
    }

    public function points(): int
    {
        return match ($this) {
            self::Point1 => 1,
            self::Point2 => 2,
            self::Point3 => 3,
            default => 0,
        };
    }
}
