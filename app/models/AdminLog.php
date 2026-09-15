<?php
/**
 * app/models/AdminLog.php
 * Historique des actions effectuées depuis l'espace administration
 * (table admin_activity_log). Chaque action de gestion (administrateurs,
 * billets, dépôts manuels...) doit être journalisée via AdminLog::record().
 */
class AdminLog
{
    /**
     * Enregistre une action administrative.
     * $action      : ex. 'creation_admin', 'promotion', 'suppression_compte', 'ajout_billet'…
     * $targetType  : 'user' ou 'bank_note'
     * $targetLabel : libellé lisible de la ressource concernée (figé, survit à sa suppression)
     */
    public static function record(
        int $adminId,
        string $adminName,
        string $action,
        string $targetType,
        ?int $targetId,
        ?string $targetLabel = null,
        ?string $details = null
    ): void {
        $stmt = db()->prepare(
            'INSERT INTO admin_activity_log (admin_id, admin_name, action, target_type, target_id, target_label, details)
             VALUES (:admin_id, :admin_name, :action, :target_type, :target_id, :target_label, :details)'
        );
        $stmt->execute([
            'admin_id'     => $adminId,
            'admin_name'   => $adminName,
            'action'       => $action,
            'target_type'  => $targetType,
            'target_id'    => $targetId,
            'target_label' => $targetLabel,
            'details'      => $details,
        ]);
    }

    /** Dernières actions, du plus récent au plus ancien. */
    public static function recent(int $limit = 100): array
    {
        $stmt = db()->prepare('SELECT * FROM admin_activity_log ORDER BY created_at DESC LIMIT :lim');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
