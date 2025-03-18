<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MongoDB\Client as MongoClient;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|max:18',
                'email' => 'required|email',
                'subject' => 'required',
                'message' => 'required|max:1800',
            ]);

            Log::info('Validation passée');

            $mongoHost = env('MONGODB_HOST', 'mongodb');
            $mongoPort = env('MONGODB_PORT', '27017');
            $mongoUrl = "mongodb://{$mongoHost}:{$mongoPort}";

            Log::info('Connexion MongoDB URL:', ['url' => $mongoUrl]);

            $mongo = new MongoClient($mongoUrl);
            Log::info('Connexion à MongoDB réussie');

            $database = $mongo->selectDatabase('ecoride');
            $collection = $database->contacts;

            $contact = [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'Non-traité',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            Log::info('Tentative d\'insertion dans MongoDB', ['data' => $contact]);
            $result = $collection->insertOne($contact);
            Log::info('Insertion réussie', ['inserted_id' => (string)$result->getInsertedId()]);

            return redirect()->route('contact')->with('success', 'Votre message a été envoyé avec succès!');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement du contact', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('contact')
                ->with('error', 'Une erreur est survenue lors de l\'envoi du message.')
                ->withInput();
        }
    }
}
