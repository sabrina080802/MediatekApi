<?php

/**
 * Gestion des exemplaires
 */
class CopyController extends DataController
{
    /** Pas d'implémentation car la mise à jour d'un exemplaire se fera dans une prochaine version */
    public function update($tableName, $id, $fields)
    {
    }
    /** Pas d'implémentation car la création d'exemplaire est faite lors du passage d'une commande à l'état livré */
    public function create($tableName, $fields)
    {
    }
    /** Pas d'implémentation car la suppression d'un exemplaire se fera dans une prochaine version*/
    public function delete($tableName, $id)
    {
    }

    /** Recherche d'exemplaires selon les champs spécifiés */
    public function find($tableName, $fields)
    {
        $fieldList = [];
        $where = '';
        $isFirst = true;
        foreach ($fields as $key => $value) {
            if ($isFirst == false) {
                $where .= ' AND';
            } else {
                $isFirst = false;
            }

            if ($key == 'id') {
                $where .= " $tableName.id LIKE :$key";
                $fieldList[$key] = "%$value%";
            } else if (strpos($key, 'id') !== false) {
                $where .= " $key = :$key";
                $fieldList[$key] = $value;
            } else {
                $where .= " $key LIKE :$key";
                $fieldList[$key] = "%$value%";
            }
        }

        $request = "SELECT * FROM $tableName WHERE$where";
        return Db::query($request, $fieldList);
    }
    /** Récupère les informations d'un exemplaire par son id */
    public function first($tableName, $id)
    {
        return Db::query("SELECT * FROM $tableName WHERE id = :id", ['id' => $id]);
    }
}
