SELECT
    i.recipe_id,
    i.recipe_name,
    IFNULL(i.initial_stock, 0) AS initial_stock,
    IFNULL(ii.total_incoming, 0) AS total_incoming,
    IFNULL(iio.total_outgoing, 0) AS total_outgoing,
    IFNULL(irf.total_returned_from, 0) AS total_returned_from,
    IFNULL(irt.total_returned_to, 0) AS total_returned_to,
    IFNULL(it.total_tainted, 0) AS total_tainted,
    ROUND(
        IFNULL(i.initial_stock, 0)
        + IFNULL(ii.total_incoming, 0)
        - IFNULL(iio.total_outgoing, 0)
        + IFNULL(irt.total_returned_to, 0)
        - IFNULL(irf.total_returned_from, 0)
        - IFNULL(it.total_tainted, 0),
        3
    ) AS total_balance,
     IFNULL(ds.quantity, 0) AS department_store_quantity

FROM (
 SELECT
        ia.recipe_id,
        r.name AS recipe_name,
        ia.quantity AS initial_stock
    FROM inventory_archive ia
    JOIN recipes r ON r.id = ia.recipe_id
    JOIN (
        SELECT 
            recipe_id,
            MAX(captured_at) AS max_captured_at
        FROM inventory_archive 
        WHERE captured_at < '2026-05-01'
          AND department_id = '01hy3km07mf7fafqn2j6388d1t'
        GROUP BY recipe_id
    ) latest ON latest.recipe_id = ia.recipe_id 
              AND latest.max_captured_at = ia.captured_at
    WHERE ia.captured_at < '2026-05-01'
      AND ia.department_id = '01hy3km07mf7fafqn2j6388d1t'
) i
LEFT JOIN (
    SELECT
        ir.recipe_id,
        SUM(ir.quantity) AS total_incoming
    FROM invoice_recipe ir
    JOIN invoices inv ON inv.id = ir.invoice_id
    WHERE inv.created_at BETWEEN '2026-05-01' AND '2026-05-30'
      AND inv.type = 'in_coming'
      AND inv.to = '01hy3km07mf7fafqn2j6388d1t'
    GROUP BY ir.recipe_id
) ii ON ii.recipe_id = i.recipe_id

LEFT JOIN (
    -- Total outgoing
    SELECT
        ir.recipe_id,
        SUM(ir.quantity) AS total_outgoing
    FROM invoice_recipe ir
    JOIN invoices inv ON inv.id = ir.invoice_id
    WHERE inv.created_at BETWEEN '2026-05-01' AND '2026-05-30'
      AND inv.type = 'out_going'
      AND inv.from = '01hy3km07mf7fafqn2j6388d1t'
    GROUP BY ir.recipe_id
) iio ON iio.recipe_id = i.recipe_id

LEFT JOIN (
    -- Returned from
    SELECT
        ir.recipe_id,
        SUM(ir.quantity) AS total_returned_from
    FROM invoice_recipe ir
    JOIN invoices inv ON inv.id = ir.invoice_id
    WHERE inv.created_at BETWEEN '2026-05-01' AND '2026-05-30'
      AND inv.type = 'returned'
      AND inv.from = '01hy3km07mf7fafqn2j6388d1t'
    GROUP BY ir.recipe_id
) irf ON irf.recipe_id = i.recipe_id

LEFT JOIN (
    -- Returned to
    SELECT
        ir.recipe_id,
        SUM(ir.quantity) AS total_returned_to
    FROM invoice_recipe ir
    JOIN invoices inv ON inv.id = ir.invoice_id
    WHERE inv.created_at BETWEEN '2026-05-01' AND '2026-05-30'
      AND inv.type = 'returned'
      AND inv.to = '01hy3km07mf7fafqn2j6388d1t'
    GROUP BY ir.recipe_id
) irt ON irt.recipe_id = i.recipe_id

LEFT JOIN (
    -- Total tainted
    SELECT
        ir.recipe_id,
        SUM(ir.quantity) AS total_tainted
    FROM invoice_recipe ir
    JOIN invoices inv ON inv.id = ir.invoice_id
    WHERE inv.created_at BETWEEN '2026-05-01' AND '2026-05-30'
      AND inv.type = 'tainted'
      AND inv.from = '01hy3km07mf7fafqn2j6388d1t'
    GROUP BY ir.recipe_id
) it ON it.recipe_id = i.recipe_id

LEFT JOIN department_store ds
    ON ds.recipe_id = i.recipe_id
  AND ds.department_id = '01hy3km07mf7fafqn2j6388d1t'

  WHERE ABS(IFNULL(ds.quantity, 0) - (
    IFNULL(i.initial_stock, 0)
    + IFNULL(ii.total_incoming, 0)
    - IFNULL(iio.total_outgoing, 0)
    + IFNULL(irt.total_returned_to, 0)
    - IFNULL(irf.total_returned_from, 0)
    - IFNULL(it.total_tainted, 0)
))>= 0.1 ;




























