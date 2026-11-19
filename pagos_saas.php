<?php
include("conn.php");
include("components/security.php");
INCLUDE("matrix.php");





?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet" href="table_styles.css">

</head>
    <style>
        :root {
            --primary: #4361ee;
            --success: #05cd99;
            --warning: #ffb547;
            --text-main: #2b3674;
            --text-muted: #a3aed0;
            --white: #ffffff;
        }

        body { font-family: 'Inter', sans-serif; background: #f4f7fe; margin: 0; }
        main { display: grid; grid-template-columns: 250px 1fr; }
        .content-area { padding: 30px; }

        /* WIDGETS */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .stat-card { 
            background: var(--white); 
            padding: 20px; 
            border-radius: 20px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08); 
        }
        .icon-circle { 
            width: 50px; height: 50px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem; 
        }
        .stat-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin: 0; }
        .stat-value { font-size: 1.4rem; color: var(--text-main); font-weight: 700; margin: 0; }

        /* FILTROS & TABLA */
        .card { background: var(--white); border-radius: 20px; padding: 25px; box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.08); }
        .filtros { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 25px; align-items: center; }
        .filtros input { padding: 10px 15px; border-radius: 12px; border: 1px solid #e0e5f2; background: #f4f7fe; outline: none; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 10px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0px 5px 15px rgba(67, 97, 238, 0.3); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid #f4f7fe; }
        td { padding: 15px; border-bottom: 1px solid #f4f7fe; color: var(--text-main); font-size: 0.9rem; }
        .monto-pago { color: var(--success); font-weight: 700; }
        .ticket-id { font-weight: 700; color: var(--primary); }

        @media (prefers-color-scheme: dark) {
            input{background-color: gray;}
            
        }
    </style>
<body>

<?php include("components/navbar.php"); ?>

<main>
    <section>
        <?php include("components/sidebar.php"); ?>
    </section>


    <section>
        <div class="card" style="margin: 15px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>gym_id</th>
                        <th>mp_payment_id</th>
                        <th>mp_preapproval_id</th>
                        <th>estado_pago</th>
                        <th>detalle_estado</th>
                        <th>tipo_tarjeta</th>
                        <th>ultimos_cuatro</th>
                        <th>fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pagos_saas as $p): ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td style="font-weight: 600;"><?php echo $p['gym_id'] ; ?></td>
                        <td class="ticket-id"><?php echo $p['mp_preapproval_id']; ?></td>
                        <td ><span class=" badge <?= ($p['estado_pago']=='approved'?'success':'warning') ?> " ><?php echo $p['estado_pago']; ?></span></td>
                        <td ><span class="system-badge"><?php echo $p['detalle_estado']; ?></span></td>
                        <td ><?php echo $p['tipo_tarjeta']; ?></td>
                        <td><?php echo $p['ultimos_cuatro']; ?></td>
                        <td><?php echo $p['fecha_cobro']; ?></td>
                        <td class="monto-pago">$<?php echo number_format($p['monto'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
   
</body>
<script src="script.js" >
   
</script>
</html>
