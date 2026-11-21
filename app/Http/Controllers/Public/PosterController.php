<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cartelera de una jornada (estilo afiche) lista para descargar como imagen.
 * Formatos: publicación 1080×1350 (por defecto) e historia 1080×1920.
 */
class PosterController extends Controller
{
    public function __invoke(Request $request, int $id, string $date): View
    {
        $tournament = Tournament::with(['club', 'venue', 'rules'])->findOrFail($id);
        $isPublic = $tournament->is_public && $tournament->status->value !== 'draft';
        abort_unless($isPublic || $request->user()?->can('view', $tournament), 404);

        $day = CarbonImmutable::createFromFormat('Y-m-d', $date) ?: abort(404);
        $games = $tournament->games()->with(['homeTeam.team', 'awayTeam.team', 'venue'])
            ->whereDate('scheduled_at', $day->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at')->get();
        abort_if($games->isEmpty(), 404);

        $story = $request->query('formato') === 'historia';
        $allDone = $games->every(fn ($g) => $g->status->isFinished());
        $count = $games->count();
        $label = $allDone ? 'Resultados' : match ($count) {
            1 => 'Partido de la jornada', 2 => 'Doble cartelera', 3 => 'Triple cartelera', default => "{$count} partidos"
        };

        $dates = $tournament->games()->whereNotNull('scheduled_at')->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(scheduled_at) d')->groupBy('d')->orderBy('d')->pluck('d');

        return view('public.poster', compact('tournament', 'games', 'day', 'story', 'label', 'dates', 'allDone'));
    }
}
