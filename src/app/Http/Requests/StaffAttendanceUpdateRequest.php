<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StaffAttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'break_times' => ['nullable', 'array'],
            'break_times.*.start' => ['nullable', 'date_format:H:i'],
            'break_times.*.end' => ['nullable', 'date_format:H:i'],
            'remarks' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'remarks.required' => '備考を記入してください',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 出勤・退勤時間のバリデーション
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');
            
            if ($clockIn && $clockOut) {
                $clockInTime = Carbon::createFromFormat('H:i', $clockIn);
                $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);
                
                if ($clockInTime->greaterThanOrEqualTo($clockOutTime)) {
                    $validator->errors()->add('clock_in', '出勤時間が不適切な値です');
                    $validator->errors()->add('clock_out', '出勤時間が不適切な値です');
                }
            }
            
            // 休憩時間のバリデーション
            $breakTimes = $this->input('break_times', []);
            
            foreach ($breakTimes as $index => $breakTime) {
                if (empty($breakTime['start']) && empty($breakTime['end'])) {
                    continue;
                }
                
                $start = $breakTime['start'] ?? null;
                $end = $breakTime['end'] ?? null;
                
                if ($start && $clockIn && $clockOut) {
                    $breakStartTime = Carbon::createFromFormat('H:i', $start);
                    $clockInTime = Carbon::createFromFormat('H:i', $clockIn);
                    $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);
                    
                    // 休憩開始が出勤時間より前、または退勤時間より後
                    if ($breakStartTime->lessThan($clockInTime) || $breakStartTime->greaterThan($clockOutTime)) {
                        $validator->errors()->add("break_times.{$index}.start", '休憩時間が不適切な値です');
                    }
                }
                
                if ($end && $clockOut) {
                    $breakEndTime = Carbon::createFromFormat('H:i', $end);
                    $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);
                    
                    // 休憩終了が退勤時間より後
                    if ($breakEndTime->greaterThan($clockOutTime)) {
                        $validator->errors()->add("break_times.{$index}.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}
