<?php

namespace App\Models\Cro;

use App\Models\BlogPost;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CroLead extends Model
{
    protected $table = 'cro_leads';

    public const STATUSES = ['NEW', 'CONTACTED', 'QUALIFIED', 'NEGOTIATION', 'WON', 'LOST'];

    public const REQUEST_TYPES = [
        'BUY', 'SELL', 'RENT', 'MORTGAGE', 'INVESTMENT', 'PRE_SALE', 'COMMERCIAL', 'LAND', 'DEMO', 'SUPPORT', 'OTHER',
    ];

    protected $fillable = [
        'uuid', 'name', 'mobile', 'email', 'request_type', 'property_type', 'city', 'location', 'budget',
        'message', 'source', 'status', 'lead_score', 'is_duplicate', 'duplicate_of', 'customer_id',
        'blog_post_id', 'article_slug', 'article_url', 'category_slug', 'landing_page',
        'first_touch_path', 'last_touch_path', 'conversion_page',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'gclid', 'keyword', 'campaign',
        'consent', 'ip_hash', 'visitor_hash', 'quality_feedback',
        'contacted_at', 'qualified_at', 'won_at', 'response_seconds', 'meta',
    ];

    protected $casts = [
        'is_duplicate' => 'boolean',
        'consent' => 'boolean',
        'meta' => 'array',
        'contacted_at' => 'datetime',
        'qualified_at' => 'datetime',
        'won_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of');
    }
}
