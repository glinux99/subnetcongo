<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

// Modèles (à adapter selon votre namespace exact)
use App\Models\User;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\ForumPost;
use App\Models\ChatGroup;
use App\Models\Message;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;

class ForumController extends Controller
{
    /**
     * ============================================================================
     * POINT D'ENTRÉE PRINCIPAL DU HUB (Vue monolithique)
     * ============================================================================
     */
 private function getSharedHubData(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $isInstructor = $user->hasRole(['instructor', 'admin']);

        // 1. FORUM : Discussions avec filtres
        $forumQuery = ForumTopic::with(['category', 'author.profile'])
            ->withCount(['replies as replies_count', 'upvotes as votes_count'])
            ->withExists(['upvoters as has_voted' => fn($q) => $q->where('user_id', $user->id)])
            ->when($request->search, function ($q, $search) {
                $q->where(fn($sub) => $sub->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%"));
            })
            ->when($request->category, fn($q, $cat) => $q->where('forum_category_id', $cat))
            ->when($request->status && $request->status !== 'all', function ($q) use ($request) {
                if ($request->status === 'resolved') $q->where('is_resolved', true);
                if ($request->status === 'unresolved') $q->where('is_resolved', false);
            })
            ->when($request->sort, function ($q, $sort) {
                if ($sort === 'popular') $q->orderByDesc('replies_count');
                elseif ($sort === 'unanswered') $q->where('replies_count', 0)->latest();
                else $q->latest('updated_at');
            }, fn($q) => $q->latest('updated_at'))
            ->orderByDesc('is_pinned');

        // 2. CHAT : Contacts & Groupes
        $contacts = User::where('id', '!=', $user->id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['instructor', 'student']))
            ->select('id', 'name')
            ->with('profile:id,user_id,avatar_path')
            ->limit(50)
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'avatar_path' => optional($c->profile)->avatar_path ? asset('media/'.$c->profile->avatar_path) : null,
                'role'        => $c->hasRole('instructor') ? 'instructor' : 'student',
                'is_online'   => cache()->has('user-is-online-' . $c->id),
            ]);

        $chatGroups = $user->chatGroups()->withCount('members')->get()->map(fn($g) => [
            'id'            => $g->id,
            'name'          => $g->name,
            'cover_path'    => $g->cover_path ? asset('storage/' . $g->cover_path) : null,
            'members_count' => $g->members_count,
        ]);

        // Messages en Lazy Loading
        $messages = Inertia::lazy(function () use ($request, $user) {
            $chatId   = $request->input('chat_id');
            $chatType = $request->input('chat_type');
            if (!$chatId) return [];

            if ($chatType === 'contact') {
                Message::where('sender_id', $chatId)->where('receiver_id', $user->id)->update(['is_read' => true]);
                return Message::with('sender:id,name')
                    ->where(fn($q) => $q->where('sender_id', $user->id)->where('receiver_id', $chatId))
                    ->orWhere(fn($q) => $q->where('receiver_id', $user->id)->where('sender_id', $chatId))
                    ->oldest()->limit(100)->get()->map(fn($m) => $this->formatMessage($m));
            }

            if ($chatType === 'group') {
                return Message::with('sender:id,name')->where('group_id', $chatId)->oldest()->limit(100)->get()->map(fn($m) => $this->formatMessage($m, true));
            }
            return [];
        });

        // 3. ACADÉMIQUE
        $assignments = [];
        $instructorAssignments = [];
        $activeCourses = [];

        if ($isInstructor) {
            $instructorAssignments = Assignment::where('instructor_id', $user->id)
                ->with('course:id,title')
                ->withCount(['submissions', 'submissions as needs_grading_count' => fn($q) => $q->where('is_graded', false)])
                ->latest()->get();
        } else {
            $activeCourses = $user->enrolledCourses()->wherePivot('status', 'active')->get();
            $assignments = Assignment::with('course:id,title')
                ->whereHas('course.students', fn($q) => $q->where('user_id', $user->id))
                ->latest()->get()->map(function ($assignment) use ($user) {
                    $submission = $assignment->submissions()->where('student_id', $user->id)->first();
                    $status = 'pending';
                    if ($submission) $status = $submission->is_graded ? 'graded' : 'submitted';
                    elseif ($assignment->due_date && Carbon::now()->isAfter($assignment->due_date)) $status = 'late';

                    return [
                        'id'           => $assignment->id,
                        'title'        => $assignment->title,
                        'instructions' => $assignment->instructions,
                        'due_date'     => $assignment->due_date,
                        'status'       => $status,
                        'type'         => $assignment->type ?? 'file',
                        'course'       => $assignment->course,
                        'score'        => $submission->score ?? null,
                    ];
                });
        }

        // 4. KPIS
        $stats = [
            'unread_messages'     => Message::where('receiver_id', $user->id)->where('is_read', false)->count(),
            'pending_assignments' => collect($assignments)->whereIn('status', ['pending', 'late'])->count(),
            'needs_grading'       => $isInstructor ? AssignmentSubmission::whereHas('assignment', fn($q) => $q->where('instructor_id', $user->id))->where('is_graded', false)->count() : 0,
            'totalThreads'        => ForumTopic::count(),
            'onlineUsers'         => User::whereHas('sessions', fn($q) => $q->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp()))->count() ?: 1,
        ];

        // Section Active
        $activeSection = 'forum';
        if ($request->has('chat_id') || $request->has('chat_type')) $activeSection = 'chat';
        elseif ($request->has('section')) $activeSection = $request->input('section');

        // On retourne tout le tableau de données prêt à être injecté
        return [
            'currentUser' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'role'   => $user->roles->first()->name ?? 'student',
                'avatar' => optional($user->profile)->avatar_path ? asset('media/' . $user->profile->avatar_path) : null,
            ],
            'activeSection'       => $activeSection,
            'categories'          => ForumCategory::select('id', 'name', 'color')->withCount('topics as threads_count')->get(),
            'filters'             => $request->only(['search', 'category', 'status', 'sort']),
            'threads'             => $forumQuery->paginate(15)->withQueryString(),
            'contacts'            => $contacts,
            'chatGroups'          => $chatGroups,
            'messages'            => $messages,
            'assignments'         => $assignments,
            'instructorAssignments'=> $instructorAssignments,
            'activeCourses'       => $activeCourses,
            'stats'               => $stats,
        ];
    }

    /**
     * ============================================================================
     * ROUTES PRINCIPALES
     * ============================================================================
     */

    public function index(Request $request)
    {

        // On rend la page en injectant toutes les données calculées
        return Inertia::render('Social/Forums', $this->getSharedHubData($request));
    }

    public function showThread(Request $request, $id)
    {
        // 1. On récupère le sujet spécifique
        $thread = ForumTopic::with([
            'category',
            'author.profile',
            'replies' => fn($q) => $q->with('author.profile')->latest()
        ])->findOrFail($id);

        $thread->incrementViews();

        // 2. On récupère TOUTES les données de base (Sidebar, contacts, etc.)
        $data = $this->getSharedHubData($request);

        // 3. On ajoute le sujet à la charge utile
        $data['currentThreadInfo'] = clone $thread;

        // 4. On force l'UI à s'ouvrir sur l'onglet forum
        $data['activeSection'] = 'forum';

        // 5. On rend exactement la même vue, mais avec les détails du sujet
        return Inertia::render('Social/Forums', $data);
    }

    public function storeThread(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'category_id' => 'required|exists:forum_categories,id',
            'content'     => 'required|string|min:5',
            'attachments' => 'nullable|array|max:3', // Frontend supporte l'envoi multiple
            'attachments.*'=> 'file|max:10240'
        ]);

        $topic = ForumTopic::create([
            'user_id'           => Auth::id(),
            'forum_category_id' => $validated['category_id'],
            'title'             => $validated['title'],
            'slug'              => Str::slug($validated['title']) . '-' . uniqid(),
            'content'           => $validated['content'],
            'status'            => 'active',
        ]);

        // Gestion basique des fichiers (A adapter si vous avez une table `Media`)
        if ($request->hasFile('attachments')) {
            $file = $request->file('attachments')[0]; // Prise en charge du 1er fichier pour la DB actuelle
            $topic->update([
                'attachment_path' => $file->store('forum/attachments', 'media'),
                'attachment_name' => $file->getClientOriginalName(),
            ]);
        }

        return Redirect::back()->with('success', 'Sujet publié avec succès.');
    }

    public function storeReply(Request $request, $threadId)
    {
        $validated = $request->validate([
            'content'     => 'required|string',
            'attachments' => 'nullable|array|max:1',
            'attachments.*'=> 'file|max:5120',
        ]);

        $topic = ForumTopic::findOrFail($threadId);
        if ($topic->is_locked) return Redirect::back()->with('error', 'Discussion verrouillée.');

        $reply = ForumPost::create([
            'forum_topic_id'  => $topic->id,
            'user_id'         => Auth::id(),
            'content'         => $validated['content'],
        ]);

        if ($request->hasFile('attachments')) {
            $file = $request->file('attachments')[0];
            $reply->update([
                'attachment_path' => $file->store('forum/replies', 'media'),
                'attachment_name' => $file->getClientOriginalName(),
            ]);
        }

        $topic->touch(); // Remonte le sujet
        return Redirect::back()->with('success', 'Réponse envoyée.');
    }

    public function storeCategory(Request $request)
    {
        // if (!Auth::user()->hasRole(['instructor', 'admin'])) abort(403);

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:forum_categories,name',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:7',
            'icon'        => 'nullable|string|max:50',
        ]);

        ForumCategory::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
        ]);

        return Redirect::back()->with('success', 'Catégorie créée avec succès.');
    }

    public function destroyThread($id)
    {
        $topic = ForumTopic::findOrFail($id);

        // Autorisation : Auteur ou Staff
        // if (Auth::id() !== $topic->user_id && !Auth::user()->hasRole(['instructor', 'admin'])) {
        //     abort(403, 'Non autorisé.');
        // }

        // Nettoyage fichiers
        if ($topic->attachment_path) Storage::disk('media')->delete($topic->attachment_path);
        foreach ($topic->replies as $reply) {
            if ($reply->attachment_path) Storage::disk('media')->delete($reply->attachment_path);
        }

        $topic->delete();
        return Redirect::route('forum.index')->with('success', 'Sujet supprimé.');
    }

    // Actions rapides sur les threads (Toggles)
    public function upvoteThread($id) {
        $topic = ForumTopic::findOrFail($id);
        $topic->upvoters()->toggle(Auth::id());
        return Redirect::back();
    }

    public function pinThread($id) {
        // if (!Auth::user()->hasRole(['instructor', 'admin'])) abort(403);
        $topic = ForumTopic::findOrFail($id);
        $topic->update(['is_pinned' => !$topic->is_pinned]);
        return Redirect::back();
    }

    public function lockThread($id) {
        // if (!Auth::user()->hasRole(['instructor', 'admin'])) abort(403);
        $topic = ForumTopic::findOrFail($id);
        $topic->update(['is_locked' => !$topic->is_locked]);
        return Redirect::back();
    }

    public function resolveThread($id) {
        $topic = ForumTopic::findOrFail($id);
        if (Auth::id() !== $topic->user_id && !Auth::user()->hasRole(['instructor', 'admin'])) abort(403);
        $topic->update(['is_resolved' => true]);
        return Redirect::back();
    }

    /**
     * ============================================================================
     * CHAT & MESSAGERIE API
     * ============================================================================
     */

    public function storeMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required_without:group_id|nullable|exists:users,id',
            'group_id'    => 'required_without:receiver_id|nullable|exists:chat_groups,id',
            'message'     => 'nullable|string',
            'attachment'  => 'nullable|file|max:10240',
        ]);

        if (empty($validated['message']) && !$request->hasFile('attachment')) {
            return Redirect::back()->with('error', 'Le message est vide.');
        }

        $msg = new Message([
            'sender_id'   => Auth::id(),
            'receiver_id' => $validated['receiver_id'] ?? null,
            'group_id'    => $validated['group_id'] ?? null,
            'content'     => $validated['message'] ?? '',
            'is_read'     => false,
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $msg->attachment_name = $file->getClientOriginalName();
            $msg->attachment_path = $file->store('chat/attachments', 'media');
        }

        $msg->save();
        return Redirect::back();
    }

    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
            'cover'   => 'nullable|image|max:2048',
        ]);

        $group = ChatGroup::create([
            'name'       => $validated['name'],
            'creator_id' => Auth::id(),
            'cover_path' => $request->hasFile('cover') ? $request->file('cover')->store('chat/groups', 'media') : null
        ]);

        $group->members()->sync(array_merge($validated['members'], [Auth::id()]));

        return Redirect::back()->with('success', 'Groupe créé.');
    }

    /**
     * ============================================================================
     * ACADÉMIQUE : DEVOIRS & QUIZ API
     * ============================================================================
     */

    public function storeAssignment(Request $request)
    {
        if (!Auth::user()->hasRole(['instructor', 'admin'])) abort(403);

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'course_id'    => 'required|exists:courses,id',
            'instructions' => 'required|string',
            'due_date'     => 'required|date',
            'max_score'    => 'nullable|numeric|min:1',
            'allow_late'   => 'boolean'
        ]);

        Assignment::create([
            ...$validated,
            'instructor_id' => Auth::id(),
            'type'          => 'file'
        ]);

        return Redirect::back()->with('success', 'Devoir publié.');
    }

    public function storeQuiz(Request $request)
    {
        if (!Auth::user()->hasRole(['instructor', 'admin'])) abort(403);

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'course_id'          => 'required|exists:courses,id',
            'description'        => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:0',
            'passing_score'      => 'required|integer|min:1|max:100',
            'questions'          => 'required|array|min:1',
            'questions.*.text'   => 'required|string',
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.options'=> 'required|array|min:2',
            'questions.*.options.*.text' => 'required|string',
            'questions.*.options.*.is_correct' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $quiz = Quiz::create([
                'title'              => $validated['title'],
                'course_id'          => $validated['course_id'],
                'instructor_id'      => Auth::id(),
                'description'        => $validated['description'],
                'time_limit_minutes' => $validated['time_limit_minutes'],
                'passing_score'      => $validated['passing_score'],
            ]);

            // Lier le Quiz en tant qu'Assignment (Architecture mixte)
            Assignment::create([
                'title'         => "[QUIZ] " . $validated['title'],
                'course_id'     => $validated['course_id'],
                'instructor_id' => Auth::id(),
                'instructions'  => $validated['description'] ?? 'Examen QCM.',
                'type'          => 'quiz',
                'quiz_id'       => $quiz->id, // Clé étrangère
            ]);

            foreach ($validated['questions'] as $qData) {
                $question = $quiz->questions()->create([
                    'text'   => $qData['text'],
                    'points' => $qData['points']
                ]);

                foreach ($qData['options'] as $oData) {
                    $question->options()->create([
                        'text'       => $oData['text'],
                        'is_correct' => $oData['is_correct']
                    ]);
                }
            }
        });

        return Redirect::back()->with('success', 'Interrogation générée.');
    }

    public function submitAssignment(Request $request)
    {
        $validated = $request->validate([
            'assignment_id'   => 'required|exists:assignments,id',
            'submitted_file'  => 'required|file|max:20480',
            'student_comment' => 'nullable|string',
        ]);

        AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $validated['assignment_id'], 'student_id' => Auth::id()],
            [
                'file_path'       => $request->file('submitted_file')->store('assignments/submissions', 'media'),
                'file_name'       => $request->file('submitted_file')->getClientOriginalName(),
                'student_comment' => $validated['student_comment'],
                'submitted_at'    => now(),
            ]
        );

        return Redirect::back()->with('success', 'Travail remis.');
    }

    public function storeGrade(Request $request)
    {
        if (!Auth::user()->hasRole(['instructor', 'admin'])) abort(403);

        $validated = $request->validate([
            'submission_id' => 'required|exists:assignment_submissions,id',
            'score'         => 'required|numeric|min:0|max:100',
            'feedback'      => 'nullable|string',
        ]);

        AssignmentSubmission::findOrFail($validated['submission_id'])->update([
            'score'     => $validated['score'],
            'feedback'  => $validated['feedback'],
            'is_graded' => true,
            'graded_at' => now(),
        ]);

        return Redirect::back()->with('success', 'Note attribuée.');
    }

    /**
     * Utilitaire de formatage pour les messages
     */
    private function formatMessage($msg, $isGroup = false)
    {
        return [
            'id'              => $msg->id,
            'sender_id'       => $msg->sender_id,
            'user'            => ['name' => optional($msg->sender)->name], // Utilisé par le front-end
            'content'         => $msg->content,
            'attachment_path' => $msg->attachment_path ? asset('storage/' . $msg->attachment_path) : null,
            'attachment_name' => $msg->attachment_name,
            'created_at'      => $msg->created_at->toISOString(),
            'is_read'         => $msg->is_read,
        ];
    }
}
