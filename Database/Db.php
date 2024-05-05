<?php

/**
 * Met à disposition des fonctions de manipulation de la base de données
 * Gère également la connexion à la base de données
 */
class Db
{
    /*
    private static $login = "if0_36469714";
    private static $pwd = "cD50zOkMSJTQ";
    private static $db = "if0_36469714_mediatek86";
    private static $host = "sql307.infinityfree.com";
    */

    private static $login = "root";
    private static $pwd = "";
    private static $db = "mediatek86";
    private static $host = "127.0.0.1";
    private static $port = 3306;

    private static $conn = null;

    /**
     * Initialise la connexion à la base de données
     */
    private static function init()
    {
        if (self::$conn != null) return;

        self::$conn = new PDO("mysql:host=" . self::$host . ';dbname=' . self::$db . ';port=' . self::$port, self::$login, self::$pwd);
        self::$conn->query('SET CHARACTER SET utf8');
    }
    /**
     * Exécute une requête de création de données
     */
    public static function insert($tableName, $values)
    {
        $fields = '';
        $params = '';
        $isFirst = true;
        foreach ($values as $key => $value) {
            if ($isFirst == false) {
                $fields .= ',';
                $params .= ',';
            } else {
                $isFirst = false;
            }

            $fields .= "`$key`";
            $params .= ":$key";
            if ($value instanceof DateTime) {
                $values[$key] = $value->format("Y-m-d H:i:s");
            }
        }

        return self::execute("INSERT INTO $tableName ($fields) VALUES ($params)", $values);
    }

    /**
     * Exécute une requête de suppression de données en se basant sur l'id
     */
    public static function delete($tableName, $id)
    {
        return self::execute("DELETE FROM $tableName WHERE id = :id", ['id' => $id]);
    }
    /**
     * Exécute une requête de mise à jour de données en se basant sur l'id pour mettre à jour les données fournies dans fields
     */
    public static function update($tableName, $id, $fields)
    {
        $query = "UPDATE $tableName SET";
        $isFirst = true;
        foreach ($fields as $key => $value) {
            if ($isFirst == false) {
                $query .= ',';
            } else {
                $isFirst = false;
            }

            if ($value instanceof DateTime) {
                $fields[$key] = $value->format("Y-m-d H:i:s");
            }
            $query .= " $key = :$key";
        }
        if ($isFirst) {
            return true;
        }

        $query .= ' WHERE id = :id';
        $fields['id'] = $id;

        return self::execute($query, $fields);
    }
    /**
     * Exécute une requête SQL
     */
    public static function execute($q, $params = null)
    {
        self::init();
        try {
            $req = self::$conn->prepare($q);
            if ($params != null) {
                foreach ($params as $key => &$value) {
                    $req->bindParam(':' . $key, $value);
                }
            }
            $result = $req->execute();

            return $result;
        } catch (Exception $e) {
            return null;
        }
    }
    /**
     * Exécute une requête SQL et renvoie le premier résultat
     */
    public static function queryFirst($q, $params = null)
    {
        self::init();

        try {
            $req = self::$conn->prepare($q);
            if ($params != null) {
                foreach ($params as $key => &$value) {
                    if ($value instanceof DateTime) {
                        $params[$key] = $value->format("Y-m-d H:i:s");
                    }
                    $req->bindParam(':' . $key, $value);
                }
            }

            if ($req->execute() === true) {
                $data = $req->fetch(PDO::FETCH_ASSOC);
                return ($data === false) ? null : $data;
            } else return null;
        } catch (Exception $e) {
            return null;
        }
    }
    /**
     * Exécute une requête SQL et renvoie tous les résultats dans un tableau
     */
    public static function query($q, $params = null)
    {
        self::init();

        try {
            $req = self::$conn->prepare($q);
            if ($params != null) {
                foreach ($params as $key => &$value) {
                    if ($value instanceof DateTime) {
                        $params[$key] = $value->format("Y-m-d H:i:s");
                    }
                    $req->bindParam(':' . $key, $value);
                }
            }
            if ($req->execute() === true) {
                return $req->fetchAll(PDO::FETCH_ASSOC);
            } else return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
