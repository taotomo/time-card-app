<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AdminAttendanceUpdateRequest extends FormRequest
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
            'clock_in_month' => ['required', 'integer', 'min:1', 'max:12'],
            'clock_in_day' => ['required', 'integer', 'min:1', 'max:31'],
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i'],
            'break_start_1' => ['nullable', 'date_format:H:i'],
            'break_end_1' => ['nullable', 'date_format:H:i'],
            'break_start_2' => ['nullable', 'date_format:H:i'],
            'break_end_2' => ['nullable', 'date_format:H:i'],
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
            $attendance = \App\Models\Attendance::find($this->route('id'));
            
            if (!$attendance) {
                return;
            }
            
            // 年月日を組み立て
            $year = Carbon::parse($attendance->clock_in)->format('Y');
            $clockInDate = sprintf('%s-%02d-%02d', $year, $this->input('clock_in_month'), $this->input('clock_in_day'));
            
            $clockInTime = $this->input('clock_in_time');
            $clockOutTime = $this->input('clock_out_time');
            
            // 出勤・退勤時刻の検証
            if ($clockInTime && $clockOutTime) {
                $clockInDateTime = Carbon::parse($clockInDate . ' ' . $clockInTime);
                $clockOutDateTime = Carbon::parse($clockInDate . ' ' . $clockOutTime);
                
                if ($clockInDateTime->greaterThanOrEqualTo($clockOutDateTime)) {
                    $validator->errors()->add('clock_in_time', '出勤時間もしくは退勤時間が不適切な値です');
                    $validator->errors()->add('clock_out_time', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }
            
            // 休憩1の検証
            $breakStart1 = $this->input('break_start_1');
            $breakEnd1 = $this->input('break_end_1');
            
            if ($breakStart1 && $clockInTime && $clockOutTime) {
                $breakStartDateTime = Carbon::parse($clockInDate . ' ' . $breakStart1);
                $clockInDateTime = Carbon::parse($clockInDate . ' ' . $clockInTime);
                $clockOutDateTime = Carbon::parse($clockInDate . ' ' . $clockOutTime);
                
                // 休憩開始が出勤より前、または退勤より後
                if ($breakStartDateTime->lessThan($clockInDateTime) || $breakStartDateTime->greaterThan($clockOutDateTime)) {
                    $validator->errors()->add('break_start_1', '休憩時間が不適切な値です');
                }
            }
            
            if ($breakEnd1 && $clockOutTime) {
                $breakEndDateTime = Carbon::parse($clockInDate . ' ' . $breakEnd1);
                $clockOutDateTime = Carbon::parse($clockInDate . ' ' . $clockOutTime);
                
                // 休憩終了が退勤より後
                if ($breakEndDateTime->greaterThan($clockOutDateTime)) {
                    $validator->errors()->add('break_end_1', '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
            
            // 休憩2の検証
            $breakStart2 = $this->input('break_start_2');
            $breakEnd2 = $this->input('break_end_2');
            
            if ($breakStart2 && $clockInTime && $clockOutTime) {
                $breakStartDateTime = Carbon::parse($clockInDate . ' ' . $breakStart2);
                $clockInDateTime = Carbon::parse($clockInDate . ' ' . $clockInTime);
                $clockOutDateTime = Carbon::parse($clockInDate . ' ' . $clockOutTime);
                
                // 休憩開始が出勤より前、または退勤より後
                if ($breakStartDateTime->lessThan($clockInDateTime) || $breakStartDateTime->greaterThan($clockOutDateTime)) {
                    $validator->errors()->add('break_start_2', '休憩時間が不適切な値です');
                }
            }
            
            if ($breakEnd2 && $clockOutTime) {
                $breakEndDateTime = Carbon::parse($clockInDate . ' ' . $breakEnd2);
                $clockOutDateTime = Carbon::parse($clockInDate . ' ' . $clockOutTime);
                
                // 休憩終了が退勤より後
                if ($breakEndDateTime->greaterThan($clockOutDateTime)) {
                    $validator->errors()->add('break_end_2', '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
