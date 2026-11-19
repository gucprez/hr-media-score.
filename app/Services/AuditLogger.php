<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Club;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Escribe la bitácora de auditoría. Nunca debe interrumpir la operación
 * principal: los fallos se registran en el log de la aplicación.
 */
class AuditLogger
{
    protected static bool $enabled = true;

    public static function disable(): void
    {
        static::$enabled = false;
    }

    public static function enable(): void
    {
        static::$enabled = true;
    }

    public static function model(string $event, Model $model, array $old, array $new): void
    {
        if (! static::$enabled) {
            return;
        }
        $attrs = $model->getAttributes();
        $clubId = $attrs['club_id']
            ?? ($model instanceof Club ? $model->getKey() : null)
            ?? (! empty($attrs['tournament_id'])
                ? Tournament::withTrashed()->whereKey($attrs['tournament_id'])->value('club_id') : null);
        if ($clubId === null && ! empty($attrs['tournament_team_id'])) {
            $clubId = TournamentTeam::query()->join('tournaments', 'tournaments.id', '=', 'tournament_teams.tournament_id')
                ->where('tournament_teams.id', $attrs['tournament_team_id'])->value('tournaments.club_id');
        }

        static::write($event, [
            'club_id' => $clubId,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'description' => static::describe($model),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
        ]);
    }

    public static function auth(string $event, ?User $user, ?string $description = null): void
    {
        static::write($event, [
            'club_id' => $user?->club_id,
            'user_id' => $user?->id,
            'auditable_type' => $user ? $user->getMorphClass() : null,
            'auditable_id' => $user?->id,
            'description' => $description,
        ]);
    }

    protected static function write(string $event, array $data): void
    {
        if (! static::$enabled) {
            return;
        }
        try {
            $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();
            AuditLog::create(array_merge([
                'user_id' => auth()->id(),
                'event' => $event,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            ], $data));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected static function describe(Model $model): ?string
    {
        $attrs = $model->getAttributes();
        if (! empty($attrs['name'])) {
            return mb_substr((string) $attrs['name'], 0, 255);
        }
        if (isset($attrs['first_name'], $attrs['last_name'])) {
            return mb_substr("{$attrs['first_name']} {$attrs['last_name']}", 0, 255);
        }
        if (isset($attrs['jersey_number'], $attrs['player_id'])) {
            $name = Player::withTrashed()->whereKey($attrs['player_id'])->selectRaw("CONCAT(first_name, ' ', last_name) n")->value('n');

            return "#{$attrs['jersey_number']} {$name}";
        }
        if (isset($attrs['team_id'])) {
            return Team::withTrashed()->whereKey($attrs['team_id'])->value('name');
        }

        return null;
    }
}
