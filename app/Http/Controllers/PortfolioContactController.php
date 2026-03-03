<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PortfolioContactController extends Controller
{
    public function logVisitor(Request $request)
    {
        $visitor = $this->buildVisitorData($request);

        try {
            $isRepeat = DB::table('visitor_logs')->where('ip_address', $visitor['ip_address'])->exists();

            DB::table('visitor_logs')->insert([
                'ip_address' => $visitor['ip_address'],
                'country' => $visitor['country'],
                'city' => $visitor['city'],
                'region' => $visitor['region'],
                'latitude' => $visitor['latitude'],
                'longitude' => $visitor['longitude'],
                'page_visited' => (string) $request->query('page', 'home'),
                'user_agent' => $visitor['user_agent'],
                'browser' => $visitor['browser'],
                'os' => $visitor['os'],
                'device_type' => $visitor['device_type'],
                'screen_resolution' => (string) $request->query('screen', 'Unknown'),
                'is_repeat_visitor' => $isRepeat ? 1 : 0,
                'visit_time' => now(),
            ]);

            return response('logged', 200);
        } catch (Throwable) {
            return response('error', 500);
        }
    }

    public function sendEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please fill all required fields',
            ], 422);
        }

        $validated = $validator->validated();

        $visitor = $this->buildVisitorData($request);
        $submissionTime = now();

        try {
            DB::table('contact_messages')->insert([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? '',
                'message' => $validated['message'],
                'ip_address' => $visitor['ip_address'],
                'country' => $visitor['country'],
                'city' => $visitor['city'],
                'region' => $visitor['region'],
                'latitude' => $visitor['latitude'],
                'longitude' => $visitor['longitude'],
                'user_agent' => $visitor['user_agent'],
                'browser' => $visitor['browser'],
                'os' => $visitor['os'],
                'device_type' => $visitor['device_type'],
                'submission_time' => $submissionTime,
            ]);
        } catch (Throwable) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save message to database',
            ], 500);
        }

        $recipient = (string) config('mail.portfolio_contact_email', 'contact@shakeeliqbal.com');
        $mapLink = ($visitor['latitude'] !== '' && $visitor['longitude'] !== '')
            ? "https://maps.google.com/?q={$visitor['latitude']},{$visitor['longitude']}"
            : null;

        $html = view('emails.portfolio-contact', [
            'validated' => $validated,
            'visitor' => $visitor,
            'mapLink' => $mapLink,
            'submissionTime' => $submissionTime->format('Y-m-d H:i:s'),
        ])->render();

        try {
            Mail::html($html, function ($message) use ($recipient, $validated) {
                $message->to($recipient)
                    ->replyTo($validated['email'], $validated['name'])
                    ->subject('New Message via Portfolio Contact Form');
            });
        } catch (Throwable) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email sending failed. Please try again.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Your message has been received, I will contact you soon.',
        ]);
    }

    private function buildVisitorData(Request $request): array
    {
        $ip = $this->getClientIp($request);
        $location = $this->getLocationFromIp($ip);

        return [
            'ip_address' => $ip,
            'country' => $location['country'] ?? 'Unknown',
            'city' => $location['city'] ?? 'Unknown',
            'region' => $location['region'] ?? 'Unknown',
            'latitude' => $location['latitude'] ?? '',
            'longitude' => $location['longitude'] ?? '',
            'user_agent' => (string) $request->userAgent(),
            'browser' => $this->getBrowserName((string) $request->userAgent()),
            'os' => $this->getOperatingSystem((string) $request->userAgent()),
            'device_type' => $this->getDeviceType((string) $request->userAgent()),
        ];
    }

    private function getClientIp(Request $request): string
    {
        $forwarded = $request->server('HTTP_X_FORWARDED_FOR');
        if (is_string($forwarded) && $forwarded !== '') {
            $parts = explode(',', $forwarded);
            return trim($parts[0]);
        }

        return (string) ($request->ip() ?? 'Unknown');
    }

    private function getLocationFromIp(string $ip): array
    {
        if ($ip === 'Unknown' || $ip === '127.0.0.1' || $ip === '::1') {
            return [
                'country' => 'Local',
                'city' => 'Local',
                'region' => 'Local',
                'latitude' => '',
                'longitude' => '',
            ];
        }

        try {
            $ipApiCo = Http::timeout(5)->get("https://ipapi.co/{$ip}/json/")->json();
            if (is_array($ipApiCo) && !empty($ipApiCo['city'])) {
                return [
                    'country' => $ipApiCo['country_name'] ?? 'Unknown',
                    'city' => $ipApiCo['city'] ?? 'Unknown',
                    'region' => $ipApiCo['region'] ?? 'Unknown',
                    'latitude' => (string) ($ipApiCo['latitude'] ?? ''),
                    'longitude' => (string) ($ipApiCo['longitude'] ?? ''),
                ];
            }
        } catch (Throwable) {
        }

        try {
            $ipApi = Http::timeout(5)->get("http://ip-api.com/json/{$ip}")->json();
            if (is_array($ipApi) && ($ipApi['status'] ?? null) === 'success') {
                return [
                    'country' => $ipApi['country'] ?? 'Unknown',
                    'city' => $ipApi['city'] ?? 'Unknown',
                    'region' => $ipApi['regionName'] ?? 'Unknown',
                    'latitude' => (string) ($ipApi['lat'] ?? ''),
                    'longitude' => (string) ($ipApi['lon'] ?? ''),
                ];
            }
        } catch (Throwable) {
        }

        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'region' => 'Unknown',
            'latitude' => '',
            'longitude' => '',
        ];
    }

    private function getBrowserName(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Safari') => 'Safari',
            str_contains($userAgent, 'OPR'), str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Trident') => 'Internet Explorer',
            str_contains($userAgent, 'Edge') => 'Edge',
            default => 'Unknown',
        };
    }

    private function getOperatingSystem(string $userAgent): string
    {
        return match (true) {
            preg_match('/windows|win32/i', $userAgent) === 1 => 'Windows',
            preg_match('/macintosh|mac os x/i', $userAgent) === 1 => 'MacOS',
            preg_match('/linux/i', $userAgent) === 1 => 'Linux',
            preg_match('/iphone|ipad|ipod/i', $userAgent) === 1 => 'iOS',
            preg_match('/android/i', $userAgent) === 1 => 'Android',
            default => 'Unknown',
        };
    }

    private function getDeviceType(string $userAgent): string
    {
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent) === 1) {
            return 'Mobile';
        }

        if (preg_match('/tablet|ipad|playbook|silk|nexus 7|nexus 10|xoom/i', $userAgent) === 1) {
            return 'Tablet';
        }

        return 'Desktop';
    }
}
