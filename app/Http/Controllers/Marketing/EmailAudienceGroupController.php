<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\EmailAudienceGroup;
use App\Models\EmailAudienceGroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailAudienceGroupController extends Controller
{
    /**
     * Liste JSON des groupes avec leurs membres (pour la page campagnes).
     */
    public function index()
    {
        $groups = EmailAudienceGroup::with('members:id,group_id,email,name')
            ->withCount('members')
            ->orderBy('name')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'description' => $g->description,
                'members_count' => $g->members_count,
                'members' => $g->members->map(fn ($m) => [
                    'id' => $m->id,
                    'email' => $m->email,
                    'name' => $m->name,
                ])->values(),
            ]);

        return response()->json(['groups' => $groups]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $group = EmailAudienceGroup::create($validated + ['created_by' => Auth::id()]);

        return response()->json(['group' => $group], 201);
    }

    public function update(Request $request, EmailAudienceGroup $audienceGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $audienceGroup->update($validated);

        return response()->json(['group' => $audienceGroup]);
    }

    public function destroy(EmailAudienceGroup $audienceGroup)
    {
        $audienceGroup->delete(); // les campagnes passent audience_group_id à null (nullOnDelete)

        return response()->json(['success' => true]);
    }

    /**
     Ajoute un ou plusieurs emails au groupe (accepte email simple ou liste).
     */
    public function addMembers(Request $request, EmailAudienceGroup $audienceGroup)
    {
        $validated = $request->validate([
            'emails' => 'required|array|min:1|max:200',
            'emails.*' => 'required|email|max:255',
            'names' => 'nullable|array',
        ]);

        $added = 0;
        foreach ($validated['emails'] as $i => $email) {
            $email = strtolower(trim($email));
            $member = EmailAudienceGroupMember::firstOrCreate(
                ['group_id' => $audienceGroup->id, 'email' => $email],
                ['name' => $validated['names'][$i] ?? null]
            );
            if ($member->wasRecentlyCreated) {
                $added++;
            }
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'members_count' => $audienceGroup->members()->count(),
        ]);
    }

    public function removeMember(EmailAudienceGroup $audienceGroup, EmailAudienceGroupMember $member)
    {
        abort_unless($member->group_id === $audienceGroup->id, 404);
        $member->delete();

        return response()->json(['success' => true]);
    }
}
