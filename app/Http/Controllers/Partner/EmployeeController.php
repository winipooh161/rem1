<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Отображает список сотрудников партнера
     */
    public function index()
    {
        $user = Auth::user();
        
        // Получаем всех сотрудников, привязанных к партнеру
        $employees = User::where('partner_id', $user->id)
            ->where('role', 'estimator')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('partner.employees.index', compact('employees'));
    }

    /**
     * Добавляет нового сотрудника
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'string',
                'regex:/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('partner_id', '!=', null);
                })
            ],
            'name' => 'nullable|string|max:255',
        ], [
            'phone.required' => 'Номер телефона обязателен для заполнения.',
            'phone.regex' => 'Номер телефона должен быть в формате +7 (790) 000-00-00.',
            'phone.unique' => 'Пользователь с таким номером телефона уже привязан к другому партнеру.',
        ]);

        $partnerId = Auth::id();
        $phone = $request->phone;
        $name = $request->name;

        Log::info('Попытка добавления сотрудника', [
            'partner_id' => $partnerId,
            'phone' => $phone,
            'name' => $name
        ]);

        try {
            // Ищем существующего пользователя по номеру телефона
            $existingUser = User::where('phone', $phone)->first();

            if ($existingUser) {
                Log::info('Найден существующий пользователь', [
                    'user_id' => $existingUser->id,
                    'current_partner_id' => $existingUser->partner_id,
                    'current_role' => $existingUser->role
                ]);

                // Если пользователь существует
                if ($existingUser->partner_id && $existingUser->partner_id != $partnerId) {
                    return back()->with('error', 'Этот пользователь уже привязан к другому партнеру.');
                }

                if ($existingUser->partner_id == $partnerId) {
                    return back()->with('error', 'Этот пользователь уже является вашим сотрудником.');
                }

                // Привязываем существующего пользователя к партнеру и меняем роль
                $updated = $existingUser->update([
                    'partner_id' => $partnerId,
                    'role' => 'estimator'
                ]);

                Log::info('Результат обновления пользователя', [
                    'updated' => $updated,
                    'user_id' => $existingUser->id,
                    'new_partner_id' => $existingUser->fresh()->partner_id,
                    'new_role' => $existingUser->fresh()->role
                ]);

                if ($updated) {
                    return redirect()->route('partner.employees.index')
                        ->with('success', 'Сотрудник ' . $existingUser->name . ' успешно добавлен в вашу команду.');
                } else {
                    return back()->with('error', 'Ошибка при обновлении данных пользователя.');
                }
            } else {
                // Если пользователя не существует, создаем нового
                $userData = [
                    'phone' => $phone,
                    'name' => $name ?: 'Сотрудник ' . substr($phone, -4),
                    'role' => 'estimator',
                    'partner_id' => $partnerId,
                    'password' => Hash::make('password123'), // Временный пароль
                ];

                Log::info('Создание нового пользователя с данными', $userData);

                $newUser = User::create($userData);

                if ($newUser) {
                    Log::info('Новый пользователь создан', [
                        'user_id' => $newUser->id,
                        'partner_id' => $newUser->partner_id,
                        'role' => $newUser->role
                    ]);

                    return redirect()->route('partner.employees.index')
                        ->with('success', 'Новый сотрудник создан и добавлен в вашу команду. Пароль по умолчанию: password123');
                } else {
                    return back()->with('error', 'Ошибка при создании нового пользователя.');
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при добавлении сотрудника', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Произошла ошибка: ' . $e->getMessage());
        }
    }

    /**
     * Отображает данные сотрудника для редактирования
     */
    public function show(User $employee)
    {
        // Проверяем, что сотрудник принадлежит текущему партнеру
        if ($employee->partner_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещен'], 403);
        }

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'role' => $employee->role,
            ]
        ]);
    }

    /**
     * Обновляет данные сотрудника
     */
    public function update(Request $request, User $employee)
    {
        // Проверяем, что сотрудник принадлежит текущему партнеру
        if ($employee->partner_id !== Auth::id()) {
            return back()->with('error', 'Доступ запрещен.');
        }

        $request->validate([
            'phone' => [
                'required',
                'string',
                'regex:/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/',
                Rule::unique('users')->ignore($employee->id)
            ],
        ], [
            'phone.required' => 'Номер телефона обязателен для заполнения.',
            'phone.regex' => 'Номер телефона должен быть в формате +7 (790) 000-00-00.',
            'phone.unique' => 'Пользователь с таким номером телефона уже существует.',
        ]);

        $employee->update([
            'phone' => $request->phone,
        ]);

        return redirect()->route('partner.employees.index')
            ->with('success', 'Данные сотрудника успешно обновлены.');
    }

    /**
     * Удаляет сотрудника из команды партнера
     */
    public function destroy(User $employee)
    {
        // Проверяем, что сотрудник принадлежит текущему партнеру
        if ($employee->partner_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещен'], 403);
        }

        // Убираем привязку к партнеру и меняем роль на клиента
        $employee->update([
            'partner_id' => null,
            'role' => 'client'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Сотрудник успешно исключен из команды.'
        ]);
    }
}
