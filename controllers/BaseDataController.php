<?php

/**
 * Gère les données de constantes.
 */
class BaseDataController extends DataController
{
    /** Pas d'implémentation car on ne met pas à jour une constante */
    public function update($tableName, $id, $fields)
    {
    }

    /** Pas d'implémentation car on ne créer pas de nouvelles constantes */
    public function create($tableName, $fields)
    {
    }

    /** Pas d'implémentation car on ne supprime pas de constantes */
    public function delete($tableName, $id)
    {
    }

    /** Pas d'implémentation car on ne cherche jamais de constantes */
    public function find($tableName, $fields)
    {
    }

    /** Récupère toutes les constantes */
    public function getAll($tableName)
    {
        return Db::query("SELECT * FROM $tableName;");
    }
}
