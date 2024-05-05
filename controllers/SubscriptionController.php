<?php

/** Gestion des abonnements */
class SubscriptionController extends DataController
{
    /** Mise à jour de la date de fin d'un abonnement */
    public function update($tableName, $id, $fields)
    {
        return Db::update($tableName, $id, [
            'dateFinAbonnement' => $fields->dateFinAbonnement
        ]);
    }
    /** Pas d'implémentation, la création d'un abonnement se fait lors de la création d'une commande avec une revue */
    public function create($tableName, $fields)
    {
    }
    /** Pas d'implémentation, la suppression d'un abonnement se fait lors de la suppression de sa commande associée */
    public function delete($tableName, $id)
    {
    }
    /** Recherche d'abonnements, seulement la recherche d'abonnements par dates est réalisable */
    public function find($tableName, $fields)
    {
        if (property_exists($fields, 'dateFinAbonnement')) {
            return Db::query(
                'SELECT abonnement.*, document.titre FROM abonnement
                INNER JOIN document ON document.id = abonnement.idRevue
                WHERE abonnement.dateFinAbonnement > NOW() AND abonnement.dateFinAbonnement < :dateFinAbonnement',
                ['dateFinAbonnement' => new DateTime($fields->dateFinAbonnement)]
            );
        } else {
            return [];
        }
    }
}
