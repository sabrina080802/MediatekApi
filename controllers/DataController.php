<?php

/** Classe abstraite pour l'implémentation d'un controller de données */
abstract class DataController
{
    /** Mise à jour de données */
    public abstract function update($tableName, $id, $fields);
    /** Création de données */
    public abstract function create($tableName, $fields);
    /** Suppression de données */
    public abstract function delete($tableName, $id);
    /** Récupère toutes les données (implémentation par défaut) */
    public function getAll($tableName)
    {
        return Db::query("SELECT * FROM $tableName;");
    }
    /** Réalise une recherche de données */
    public abstract function find($tableName, $fields);
    /** Récupère les informations d'une donnée par son id (implémentation par défaut) */
    public function first($tableName, $id)
    {
        return Db::queryFirst("SELECT * FROM $tableName WHERE id = :id", ['id' => $id]);
    }
}
