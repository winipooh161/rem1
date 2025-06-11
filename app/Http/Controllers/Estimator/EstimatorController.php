<?php

namespace App\Http\Controllers\Estimator;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstimatorController extends Controller
{
    /**
     * Отображает панель управления сметчика
     */
    public function index()
    {
        $user = Auth::user();
        
        // Получаем проекты партнера, к которому привязан сметчик, и где он назначен как исполнитель
        $assignedProjectIds = Project::where('estimator_id', $user->id)
            ->whereHas('partner', function($query) use ($user) {
                $query->where('id', $user->partner_id);
            })
            ->pluck('id');
        
        // Статистика для сметчика (только по собственным сметам)
        $stats = [
            'total_estimates' => Estimate::where('user_id', $user->id)->count(),
            'draft_estimates' => Estimate::where('user_id', $user->id)->where('status', 'draft')->count(),
            'pending_estimates' => Estimate::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved_estimates' => Estimate::where('user_id', $user->id)->where('status', 'approved')->count(),
            'assigned_projects' => $assignedProjectIds->count(),
        ];

        // Последние созданные сметы (только собственные)
        $recent_estimates = Estimate::with('project')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Сметы, требующие внимания (черновики и на рассмотрении, только собственные)
        $pending_estimates = Estimate::with('project')
            ->where('user_id', $user->id)
            ->whereIn('status', ['draft', 'pending'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('estimator.dashboard', compact('stats', 'recent_estimates', 'pending_estimates'));
    }
}
