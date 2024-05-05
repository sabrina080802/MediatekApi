<?php

/**
 * Base d'implémentation pour les controllers de documents (revue, livre et dvd)
 */
abstract class DocumentController extends DataController
{
    /** Récupère les informations d'un livre ou dvd en plus de celles du document associé */
    public function getAll($tableName)
    {
        return Db::query("SELECT * FROM $tableName
            INNER JOIN document ON document.id = $tableName.id;");
    }

    /** Recherche un document et renvoie les informations des livres ou dvd associés */
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

        $request = "SELECT * FROM $tableName INNER JOIN document ON document.id = $tableName.id WHERE$where";
        return Db::query($request, $fieldList);
    }

    /** Récupère les informations d'un livre ou dvd en plus des informations du document associé */
    public function first($tableName, $id)
    {
        return Db::queryFirst("SELECT * FROM $tableName INNER JOIN document ON document.id = $tableName.id WHERE $tableName.id = :id", ['id' => $id]);
    }

    /** Supprime un document par son id. Si un exemplaire est associé, on interdit la suppression */
    public function delete($tableName, $id)
    {
        $copy = Db::queryFirst('SELECT * FROM exemplaire WHERE id = :id', ['id' => $id]);
        if ($copy != null) {
            return ['error' => 'Vous ne pouvez pas supprimer un document pour lequel il y a au moins un exemplaire'];
        }
        $command = Db::queryFirst('SELECT * FROM commandedocument WHERE idLivreDvd = :id', ['id' => $id]);
        if ($command != null) {
            return ['error' => 'Vous ne pouvez pas supprimer un document pour lequel il y a au moins une commande'];
        }

        Db::delete($tableName, $id);
        Db::delete('livres_dvd', $id);
        return Db::delete('document', $id);
    }
}
