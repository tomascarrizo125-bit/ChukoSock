<?php
/** Cancela un pedido y devuelve a la venta las prendas que había reservado. */
function cancelar_pedido(int $idPedido): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        q("UPDATE pedido SET Estado = 'Cancelado' WHERE ID_Pedido = ?", [$idPedido]);
        q("UPDATE prenda SET Estado = 'Aprobada' WHERE Estado = 'Vendida' AND ID_Prenda IN (SELECT ID_Prenda FROM detalle_pedido WHERE ID_Pedido = ?)", [$idPedido]);
        $pdo->commit();
    } catch (Throwable $t) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $t; }
}
/** Prendas de un pedido (con vendedor). */
function items_pedido(int $idPedido): array
{
    return filas('SELECT d.*, p.Titulo, p.ID_Vendedor, u.AliasPublico FROM detalle_pedido d
        JOIN prenda p ON p.ID_Prenda = d.ID_Prenda JOIN usuario u ON u.ID_Usuario = p.ID_Vendedor WHERE d.ID_Pedido = ?', [$idPedido]);
}
