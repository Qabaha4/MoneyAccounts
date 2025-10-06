<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = Auth::id();
        // Get account ID from route parameter if updating
        $accountId = request()->route('account')?->id;

        $nameRule = [
            'required',
            'string',
            'max:255',
            Rule::unique('accounts')->where(function ($query) use ($userId) {
                return $query->where('user_id', $userId);
            }),
        ];

        // If we're updating an existing account, exclude it from the uniqueness check
        if ($accountId) {
            $nameRule[3] = $nameRule[3]->ignore($accountId);
        }

        return [
            'name' => $nameRule,
            'currency_id' => 'required|exists:currencies,id',
            'type' => 'required|in:checking,savings,credit,investment',
            'initial_balance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Account name is required.',
            'name.unique' => 'You already have an account with this name.',
            'name.min' => 'Account name must be at least 2 characters.',
            'currency_id.required' => 'Please select a currency.',
            'currency_id.exists' => 'Selected currency is not available.',
            'type.required' => 'Please select an account type.',
            'type.in' => 'Invalid account type selected.',
            'initial_balance.numeric' => 'Initial balance must be a valid number.',
            'initial_balance.min' => 'Initial balance cannot be less than -999,999,999.99.',
            'initial_balance.max' => 'Initial balance cannot be more than 999,999,999.99.',
            'initial_balance.regex' => 'Initial balance can have at most 4 decimal places.',
            'description.max' => 'Description cannot exceed 500 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'currency_id' => 'currency',
            'initial_balance' => 'initial balance',
            'is_active' => 'active status'
        ];
    }

    /**
     * Get validated data with additional processing for account creation.
     */
    public function getValidatedDataForCreation(): array
    {
        $validated = $this->validated();
        
        // Set required fields for creation
        $validated['user_id'] = Auth::id();
        $validated['balance'] = $validated['initial_balance'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Ensure initial_balance defaults to 0 if not provided
        if (!isset($validated['initial_balance'])) {
            $validated['initial_balance'] = 0;
        }

        return $validated;
    }

    /**
     * Get validated data for account updates.
     */
    public function getValidatedDataForUpdate(): array
    {
        $validated = $this->validated();
        
        // Remove fields that shouldn't be updated
        unset($validated['initial_balance'], $validated['user_id']);
        
        return $validated;
    }
}