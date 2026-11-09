<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccurateListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'database_id' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'id' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'max:50'],
            'warehouse_id' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function providerQuery(): array
    {
        return array_filter([
            'sp.page' => $this->integer('page', 1),
            'sp.pageSize' => $this->integer('pageSize', 20),
            'filter.transDate.from' => $this->input('date_from'),
            'filter.transDate.to' => $this->input('date_to'),
            'filter.status' => $this->input('status'),
            'filter.warehouse.id' => $this->input('warehouse_id'),
            'filter.vendor.id' => $this->input('supplier_id'),
            'id' => $this->input('id'),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
