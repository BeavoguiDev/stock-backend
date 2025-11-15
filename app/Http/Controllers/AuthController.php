<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validation des données
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Création de l'utilisateur
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Réponse JSON
        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        // Validation des données
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        // Connexion de l’utilisateur avec Sanctum
        Auth::login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion réussie',
            'user' => $user
        ], 200);
        
    }

    public function logout(Request $request)
{
    if (Auth::check()) {
        Auth::guard('web')->logout();
    }

    return response()->json([
        'message' => 'Déconnexion réussie'
    ]);
}

}
