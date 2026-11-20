<?php

namespace Modules\Commercial\Services;

use Illuminate\Support\Facades\DB;
use Modules\Commercial\Models\Customer;

class CustomerService
{
    public function __construct(private SequenceService $seq) {}

    public function create(array $data, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($data) {
            $c = new Customer($data + ['is_active' => true]);
            $c->forceFill([
                'code' => $this->seq->customer(),
                'company_id' => $data['company_id'] ?? \Modules\Organization\Models\Company::value('id'),
            ])->save();
            return $c;
        });
    }

    /** Find likely duplicates by phone/email — used before lead conversion. */
    public function findDuplicates(?string $phone, ?string $email): \Illuminate\Support\Collection
    {
        return Customer::query()
            ->where(fn ($q) => $q
                ->when($phone, fn ($w, $v) => $w->orWhere('phone', $v)->orWhere('whatsapp', $v))
                ->when($email, fn ($w, $v) => $w->orWhere('email', $v)))
            ->when(! $phone && ! $email, fn ($q) => $q->whereRaw('1 = 0'))
            ->limit(10)->get();
    }
}
