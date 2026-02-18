<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 50)->default('admin');
            $table->string('status', 50)->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_logout_at')->nullable();
            $table->timestamp('password_reset_requested_at')->nullable();
            $table->string('active_session_key', 191)->nullable();
            $table->timestamp('session_last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        if (!Schema::hasTable('users')) {
            return;
        }

        $rows = DB::table('users')
            ->whereRaw('LOWER(COALESCE(role, "")) = ?', ['admin'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $name = trim((string) ($row->name ?? ''));
            if ($name === '') {
                $name = 'Admin';
            }

            $usernameSeed = trim((string) ($row->username ?? ''));
            if ($usernameSeed === '') {
                $usernameSeed = explode('@', (string) ($row->email ?? ''))[0] ?? $name;
            }
            $username = $this->buildUniqueUsername($usernameSeed);
            $email = $this->buildUniqueEmail((string) ($row->email ?? ''), $username);

            $status = strtolower(trim((string) ($row->status ?? 'active')));
            if (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
                $status = 'active';
            }
            $isActive = array_key_exists('is_active', (array) $row)
                ? (bool) $row->is_active
                : $status === 'active';

            DB::table('admins')->insert([
                'id' => (int) $row->id,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'email_verified_at' => $row->email_verified_at ?? null,
                'password' => (string) ($row->password ?? ''),
                'role' => 'admin',
                'status' => $status,
                'is_active' => $isActive,
                'last_login_at' => $row->last_login_at ?? null,
                'last_logout_at' => $row->last_logout_at ?? null,
                'password_reset_requested_at' => $row->password_reset_requested_at ?? null,
                'active_session_key' => $row->active_session_key ?? null,
                'session_last_seen_at' => $row->session_last_seen_at ?? null,
                'remember_token' => $row->remember_token ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }

        DB::table('users')
            ->whereRaw('LOWER(COALESCE(role, "")) = ?', ['admin'])
            ->delete();
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasTable('users')) {
            $rows = DB::table('admins')->orderBy('id')->get();
            foreach ($rows as $row) {
                $email = strtolower(trim((string) ($row->email ?? '')));
                if ($email === '' || DB::table('users')->where('email', $email)->exists()) {
                    continue;
                }

                DB::table('users')->insert([
                    'name' => (string) ($row->name ?? 'Admin'),
                    'username' => (string) ($row->username ?? 'admin'),
                    'email' => $email,
                    'email_verified_at' => $row->email_verified_at ?? null,
                    'password' => (string) ($row->password ?? ''),
                    'role' => 'admin',
                    'status' => (string) ($row->status ?? 'active'),
                    'is_active' => (bool) ($row->is_active ?? true),
                    'last_login_at' => $row->last_login_at ?? null,
                    'last_logout_at' => $row->last_logout_at ?? null,
                    'password_reset_requested_at' => $row->password_reset_requested_at ?? null,
                    'active_session_key' => $row->active_session_key ?? null,
                    'session_last_seen_at' => $row->session_last_seen_at ?? null,
                    'remember_token' => $row->remember_token ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        Schema::dropIfExists('admins');
    }

    private function buildUniqueUsername(string $seed): string
    {
        $base = Str::lower(Str::ascii(trim($seed)));
        $base = preg_replace('/[^a-z0-9_]+/', '_', $base) ?? '';
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'admin';
        }

        $candidate = $base;
        $counter = 1;
        while (DB::table('admins')->where('username', $candidate)->exists()) {
            $candidate = "{$base}_{$counter}";
            $counter++;
        }

        return $candidate;
    }

    private function buildUniqueEmail(string $seed, string $username): string
    {
        $normalized = strtolower(trim($seed));
        if ($normalized === '' || !str_contains($normalized, '@')) {
            $normalized = "{$username}@pwdportal.local";
        }

        [$local, $domain] = array_pad(explode('@', $normalized, 2), 2, 'pwdportal.local');
        $local = trim($local) !== '' ? trim($local) : $username;
        $domain = trim($domain) !== '' ? trim($domain) : 'pwdportal.local';

        $candidate = "{$local}@{$domain}";
        $counter = 1;
        while (DB::table('admins')->where('email', $candidate)->exists()) {
            $candidate = "{$local}+{$counter}@{$domain}";
            $counter++;
        }

        return $candidate;
    }
};
