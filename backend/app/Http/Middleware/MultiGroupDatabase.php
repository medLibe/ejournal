<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MultiGroupDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $encryptedDb = $request->query('db'); //from url

        if (!$encryptedDb) {
            Log::error("DB parameter tidak ditemukan");

            return response()->json([
                'data'      => $encryptedDb,
                'message'   => 'Parameter invalid.'
            ], 400);
        }

        // decode base64
        $decodedDb = base64_decode($encryptedDb, true);

        // make sure result fo decode is valid
        if ($decodedDb === false || !is_numeric($decodedDb)) {
            return response()->json([
                'message'   => 'Database tidak valid',
                'decodeDb'  => $decodedDb,
            ], 400);
        }

        // database list based on encrypted ids with md5
        $mappingDB = config('multidb.databases');

        if (!isset($mappingDB[$decodedDb])) {
            Log::error("Database tidak ditemukan di mapping: " . $decodedDb);
            return response()->json([
                'message' => 'Database tidak valid',
                'decodeDb'  => $decodedDb,
                'mapping_decodeDb'  => $mappingDB[$decodedDb],
            ], 400);
        }

        // set selected laravel db
        Config::set('database.connections.mysql.database', $mappingDB[$decodedDb]);
        DB::purge('mysql');

        Log::info('Middleware MultiGroupDatabase running', [
            'db_param' => $request->query('db'),
            'decoded_db' => $decodedDb ?? null,
            'selected_database' => config('database.connections.mysql.database')
        ]);


        return $next($request);

    }
}
