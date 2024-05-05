<?php

require './Database/Db.php';


/**
 * Gère les authentifications
 */
class AuthController
{
    public static $user;

    /**
     * Vérifie si la requête est authentifiée
     */
    public static function isAuthenticated()
    {
        if (!isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return false;
        }

        $authResult = self::getAuthenticationResult($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        if ($authResult['responseType'] == 0) {
            self::$user = $authResult['result'];
            return true;
        }

        return false;
    }
    /**
     * Tente une authentification en utilisant le username et le password fourni en POST et renvoie le résultat
     * Cette fonction ne doit pas être appelée dans une requête autre que GET
     */
    public static function authenticate()
    {
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            return self::missingValues();
        }

        return self::getAuthenticationResult($_POST['username'], $_POST['password']);
    }
    /**
     * Exécute l'authentification en utilisant le username et le password en paramètre
     */
    private static function getAuthenticationResult($username, $password)
    {
        $accountByUsername = Db::queryFirst('SELECT * FROM account WHERE username = :username', ['username' => $username]);
        if ($accountByUsername == null) {
            return self::badAccount();
        }

        if (!password_verify($password, $accountByUsername['password'])) {
            return self::badAccount();
        }

        $rank = Db::queryFirst('SELECT * FROM `rank` WHERE id = :rank_id', ['rank_id' => $accountByUsername['rank_id']]);
        if ($rank == null || $rank['name'] == 'culture') {
            return self::accessDenied();
        }

        return self::success($accountByUsername, $rank);
    }
    /**
     * Renvoie un résultat d'authentification SUCCESS
     */
    private static function success($accountData, $rankData)
    {
        return [
            'responseType' => 0,
            'result' => [
                'id' => $accountData['id'],
                'username' => $accountData['username'],
                'rank' => $rankData
            ]
        ];
    }
    /**
     * Renvoie un résultat d'authentification ACCESS_DENIED (accès refusé)
     */
    private static function accessDenied()
    {
        return [
            'responseType' => 4,
            'error' => 'Vous n\'avez pas accès à cette application'
        ];
    }
    /**
     * Renvoie un résultat d'authentification BAD_ACCOUNT (ce compte n'existe pas)
     */
    private static function badAccount()
    {
        return [
            'responseType' => 1,
            'error' => 'Ce compte n\'existe pas'
        ];
    }
    /**
     * Renvoie un résultat d'authentification MISSING_VALUES (données manquantes)
     */
    private static function missingValues()
    {
        return [
            'responseType' => 3,
            'error' => 'Certaines valeurs sont manquantes'
        ];
    }
}
