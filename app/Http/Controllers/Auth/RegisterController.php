<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $this->validator($request->all())->validate();

            $user = $this->create($request->all());

            $this->guard()->login($user);

            return $this->registered($request, $user)
                ?: redirect($this->redirectPath());
                
        } catch (QueryException $e) {
            // Детальное логирование ошибки базы данных
            Log::error('Ошибка базы данных при регистрации: ' . $e->getMessage(), [
                'error_code' => $e->getCode(),
                'sql' => $e->getSql() ?? 'Недоступно',
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = 'Ошибка подключения к базе данных. ';
            
            // Добавляем детали в режиме отладки
            if (config('app.debug')) {
                $errorMessage .= 'Детали ошибки: ' . $e->getMessage();
            } else {
                $errorMessage .= 'Пожалуйста, попробуйте позже или сообщите администратору.';
            }
            
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['db_error' => $errorMessage]);
        } catch (Exception $e) {
            // Общая обработка других исключений
            Log::error('Ошибка при регистрации: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.']);
        }
    }

    /**
     * Валидируем данные перед загрузкой в БД
     * 
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255', 'regex:/^[А-Яа-яЁё\s]+$/u'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.regex' => 'Имя должно содержать только русские буквы и пробелы (без цифр и других символов).',
            'phone.unique' => 'Этот номер телефона уже зарегистрирован.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'client', // Устанавливаем роль 'client' по умолчанию
        ]);
    }
}
