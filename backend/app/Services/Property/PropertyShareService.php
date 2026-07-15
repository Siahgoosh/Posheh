<?php

namespace App\Services\Property;

use App\Models\Property;
use App\Models\PropertyShare;
use App\Models\User;

class PropertyShareService
{
    public function buildMessage(Property $property, ?string $officeName = null): string
    {
        $lines = [];
        $lines[] = '🏠 '.($property->code ?? 'ملک');
        $lines[] = $property->type?->label() ?? '';

        if ($property->property_category) {
            $lines[] = $property->property_category->label();
        }

        if ($property->price) {
            $lines[] = '💰 قیمت: '.number_format($property->price).' تومان';
        }

        if ($property->deposit || $property->rent) {
            $parts = [];
            if ($property->deposit) {
                $parts[] = 'رهن '.number_format($property->deposit);
            }
            if ($property->rent) {
                $parts[] = 'اجاره '.number_format($property->rent);
            }
            $lines[] = implode(' · ', $parts).' تومان';
        }

        if ($property->area) {
            $lines[] = '📐 متراژ: '.$property->area.' متر';
        }

        if ($property->rooms !== null) {
            $lines[] = '🛏 خواب: '.$property->rooms;
        }

        $location = array_filter([
            $property->city,
            $property->district,
            $property->neighborhood,
        ]);
        if ($location) {
            $lines[] = '📍 '.implode('، ', $location);
        }

        if ($property->description) {
            $desc = mb_substr(trim($property->description), 0, 200);
            $lines[] = $desc.(mb_strlen($property->description) > 200 ? '…' : '');
        }

        if ($property->qr_token) {
            $base = rtrim(config('app.frontend_url', config('app.url')), '/');
            $lines[] = '🔗 '.$base.'/p/qr/'.$property->qr_token;
        }

        if ($officeName) {
            $lines[] = '— '.$officeName;
        }

        return implode("\n", $lines);
    }

    public function buildAdCopy(Property $property): string
    {
        $type = $property->type?->label() ?? 'ملک';
        $area = $property->area ? $property->area.' متری' : '';
        $location = $property->neighborhood ?: ($property->district ?: $property->city ?: '');

        $headline = trim("{$type} {$area} {$location}");

        $priceLine = '';
        if ($property->price) {
            $priceLine = 'قیمت: '.number_format($property->price).' تومان';
        } elseif ($property->deposit || $property->rent) {
            $priceLine = 'رهن '.number_format($property->deposit ?? 0).' · اجاره '.number_format($property->rent ?? 0);
        }

        $features = [];
        if ($property->has_parking) {
            $features[] = 'پارکینگ';
        }
        if ($property->has_elevator) {
            $features[] = 'آسانسور';
        }
        if ($property->has_storage) {
            $features[] = 'انباری';
        }
        if ($property->rooms !== null) {
            $features[] = $property->rooms.' خواب';
        }

        $body = $headline."\n";
        if ($priceLine) {
            $body .= $priceLine."\n";
        }
        if ($features) {
            $body .= implode(' · ', $features)."\n";
        }
        if ($property->description) {
            $body .= mb_substr(trim($property->description), 0, 300);
        }

        return trim($body);
    }

    public function qualityScore(Property $property): int
    {
        $score = 0;

        if ($property->code) {
            $score += 10;
        }
        if ($property->price || $property->rent) {
            $score += 15;
        }
        if ($property->area) {
            $score += 10;
        }
        if ($property->city && $property->district) {
            $score += 10;
        }
        if ($property->description && mb_strlen($property->description) >= 50) {
            $score += 15;
        }
        if ($property->media && $property->media->count() > 0) {
            $score += min(20, $property->media->count() * 5);
        }
        if ($property->latitude && $property->longitude) {
            $score += 10;
        }
        if ($property->owner_mobile) {
            $score += 10;
        }

        return min(100, $score);
    }

    public function shareLinks(string $message, string $recipientMobile): array
    {
        $mobile = preg_replace('/\D/', '', $recipientMobile);
        if (str_starts_with($mobile, '0')) {
            $mobile = '98'.substr($mobile, 1);
        } elseif (! str_starts_with($mobile, '98')) {
            $mobile = '98'.$mobile;
        }

        $encoded = rawurlencode($message);

        return [
            'whatsapp' => "https://wa.me/{$mobile}?text={$encoded}",
            'telegram' => "https://t.me/share/url?url=&text={$encoded}",
            'sms' => "sms:+{$mobile}?body={$encoded}",
        ];
    }

    public function logShare(User $user, Property $property, string $channel, ?string $recipientMobile): void
    {
        PropertyShare::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'channel' => $channel,
            'recipient_mobile' => $recipientMobile,
        ]);
    }
}
