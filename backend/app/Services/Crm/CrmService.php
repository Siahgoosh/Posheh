<?php

namespace App\Services\Crm;

use App\Models\Contact;
use App\Models\ContactActivity;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;

class CrmService
{
    public function ensureDefaultPipeline(int $officeId): Pipeline
    {
        $pipeline = Pipeline::where('office_id', $officeId)->where('is_default', true)->first();

        if ($pipeline) {
            return $pipeline;
        }

        $pipeline = Pipeline::create([
            'office_id' => $officeId,
            'name' => 'فروش املاک',
            'is_default' => true,
        ]);

        $stages = [
            ['name' => 'سرنخ جدید', 'color' => '#6366f1', 'probability' => 10],
            ['name' => 'تماس گرفته', 'color' => '#8b5cf6', 'probability' => 25],
            ['name' => 'بازدید', 'color' => '#f59e0b', 'probability' => 50],
            ['name' => 'مذاکره', 'color' => '#10b981', 'probability' => 75],
            ['name' => 'قرارداد', 'color' => '#22c55e', 'probability' => 90],
            ['name' => 'موفق', 'color' => '#16a34a', 'probability' => 100],
        ];

        foreach ($stages as $i => $stage) {
            PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                ...$stage,
                'sort_order' => $i,
            ]);
        }

        return $pipeline->load('stages');
    }

    public function logActivity(User $user, Contact $contact, string $type, string $subject, ?string $body = null, ?int $dealId = null): ContactActivity
    {
        return ContactActivity::create([
            'office_id' => $contact->office_id,
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'deal_id' => $dealId,
            'type' => $type,
            'subject' => $subject,
            'body' => $body,
            'completed_at' => now(),
        ]);
    }

    public function createDeal(User $user, array $data): Deal
    {
        $pipeline = $this->ensureDefaultPipeline($user->office_id);
        $stageId = $data['pipeline_stage_id']
            ?? $pipeline->stages()->orderBy('sort_order')->value('id');

        return Deal::create([
            'office_id' => $user->office_id,
            'contact_id' => $data['contact_id'],
            'property_id' => $data['property_id'] ?? null,
            'pipeline_stage_id' => $stageId,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'created_by' => $user->id,
            'title' => $data['title'],
            'value' => $data['value'] ?? 0,
            'status' => 'open',
            'probability' => $data['probability'] ?? null,
            'expected_close_at' => $data['expected_close_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
