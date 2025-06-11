<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показать профиль пользователя.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('profile.index', ['user' => Auth::user()]);
    }

    /**
     * Показать форму для редактирования профиля пользователя.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    /**
     * Обновить профиль пользователя.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[А-Яа-яЁё\s]+$/u'],
            'phone' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ], [
            'name.regex' => 'Имя должно содержать только русские буквы и пробелы (без цифр и других символов).',
        ]);

        // Обработка аватара
        if ($request->hasFile('avatar')) {
            // Удаление старого аватара
            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }

            // Сохранение нового аватара
            $avatarName = time() . '.' . $request->avatar->extension();
            $request->avatar->storeAs('avatars', $avatarName, 'public');
            $user->avatar = $avatarName;
        }

        // Очистка номера телефона от форматирования
        $phone = preg_replace('/[^0-9]/', '', $request->phone);

        $user->name = $request->name;
        $user->phone = $phone;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Профиль успешно обновлен.');
    }

    /**
     * Показать форму для изменения пароля.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    /**
     * Изменить пароль пользователя.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        // Проверка текущего пароля
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Текущий пароль указан неверно.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Пароль успешно изменен.');
    }
}
