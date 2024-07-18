<?php
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "backup_gasmaule";

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión errónea: " . $conn->connect_error);
}

// Configurar la conexión para manejar caracteres UTF-8
$conn->set_charset("utf8");

// Obtener el RUT del POST
if (isset($_POST['rut']) && !empty($_POST['rut'])) {
    $rut = $_POST['rut'];
    
    // Consulta SQL
    $sql = "SELECT 
                v.numero_documento AS Numero_documento, 
                ev.descripcion AS Estado_Venta, 
                t.nombre_tipo_documento_venta AS Tipo_documento,
                ven.NOMBRE_CLIENTE AS Vendedor,
                caj.descripcion AS Caja,
                com_caja.NOMBRE_COMUNA AS Comuna_Caja,
                per.rut AS Rut_cliente, 
                per.nombre_cliente AS Nombre_cliente, 
                per.celular_cliente AS Celular, 
                com.NOMBRE_COMUNA AS Comuna,
                per.direccion_cliente AS Direccion, 
                p.nombre_producto AS Producto, 
                pv.cantidad AS Cargas, 
                SUM(CASE 
                    WHEN pv.id IS NOT NULL AND pv.id_producto = 11 THEN pv.cantidad
                    WHEN pv.id IS NOT NULL AND pv.id_producto = 15 THEN pv.cantidad 
                    WHEN pv.id IS NOT NULL AND pv.id_producto = 45 THEN pv.cantidad 
                    ELSE 0 
                END) AS Cilindros, 
                pv.cantidad * p.peso AS Kilos, 
                CASE 
                    WHEN v.id_estado_venta = 3 THEN v.monto_efectivo + v.monto_cheque + v.monto_debito + v.monto_tarjeta_credito + v.monto_webpay 
                    WHEN pv.id IS NOT NULL THEN SUM(pv.cantidad * (pv.precio_venta - pv.descuento_convenio)) 
                    ELSE 0 
                END AS Total, 
                v.fecha_venta AS Fecha_venta, 
                c.nombre_convenio AS Convenio, 
                pv.descuento_convenio AS Descuento
            FROM 
                ventas v
            LEFT JOIN productos_venta pv ON pv.id_venta = v.id
            LEFT JOIN productos p ON pv.id_producto = p.id
            LEFT JOIN personas per ON v.id_cliente = per.id
            LEFT JOIN personas ven ON v.id_vendedor = ven.id
            LEFT JOIN tipo_documento_venta t ON t.id = v.id_tipo_documento_venta
            LEFT JOIN convenios c ON v.id_convenio = c.id
            LEFT JOIN comunas com ON per.ID_COMUNA = com.ID
            LEFT JOIN cajas caj ON v.id_caja = caj.id
            LEFT JOIN estados_venta ev ON ev.id = v.id_estado_venta  
            LEFT JOIN comunas com_caja ON caj.id_comuna = com_caja.id
            WHERE  
                per.rut = '$rut'
                AND v.fecha_venta BETWEEN '2024-01-01' AND CURRENT_DATE
                AND ev.descripcion != 'NULA' 
                AND (pv.id_producto IN (11, 15, 45, 46, 47, 20, 21))
            GROUP BY 
                v.id
            ORDER BY 
                v.fecha_venta DESC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        echo '
<div class="card shadow mb-4">
    <div class="card-header py-3">
    <input type="text" id="searchInput" class="form-control" mb-4 placeholder="Buscar..." width="30px">
    
    </div>
    <div class="card-body">
    
        <div class="table-responsive">
        
            <table id="dataTable" class="table table-sm table-bordered table-hover" width="80%" cellspacing="0"style="height: 300px; overflow-y: scroll;" >
                <thead>
                    <tr>
                        <th>N°Documento</th>
                        <th>Estado_Venta</th>
                        <th>Documento</th>                        
                        <th>Caja</th>
                        <th>Comuna</th>
                        <th>Rut</th>
                        <th>Nombre</th>
                        <th>Celular</th>
                        <th>Comuna</th>
                        <th>Direccion</th>
                        <th>Producto</th>
                        <th>Cilindros</th>
                        <th>Kilos</th>
                        <th>Total</th>
                        <th>Fecha_venta</th>
                        <th>Convenio</th>
                        <th>Descuento</th>
                    </tr>
                </thead>
                <tbody>';
while($row = $result->fetch_assoc()) {
    echo '<tr>
            <td>'.$row["Numero_documento"].'</td>
            <td>'.$row["Estado_Venta"].'</td>
            <td>'.$row["Tipo_documento"].'</td>                    
            <td>'.$row["Caja"].'</td>
            <td>'.$row["Comuna_Caja"].'</td>
            <td>'.$row["Rut_cliente"].'</td>
            <td>'.$row["Nombre_cliente"].'</td>
            <td>'.$row["Celular"].'</td>
            <td>'.$row["Comuna"].'</td>
            <td>'.$row["Direccion"].'</td>
            <td>'.$row["Producto"].'</td>
            <td>'.$row["Cilindros"].'</td>
            <td>'.$row["Kilos"].'</td>
            <td>'.$row["Total"].'</td>
            <td>'.$row["Fecha_venta"].'</td>
            <td>'.$row["Convenio"].'</td>
            <td>'.$row["Descuento"].'</td>
          </tr>';
}
echo '</tbody></table></div>
    </div>
</div>';

} else {
        echo "No results found.";
    }
} else {
    echo "RUT is not set or empty.";
}

$conn->close();
?>

<script>
  // Se obtiene una referencia al campo de entrada de búsqueda
  const searchInput = document.getElementById('searchInput');
 
  // Se obtiene una referencia a la tabla y a las filas de datos
  const dataTable = document.getElementById('dataTable');
  const dataRows = dataTable.getElementsByTagName('tr');

  // Se agrega un evento de escucha al campo de entrada de búsqueda
  searchInput.addEventListener('input', function() {
    const searchText = searchInput.value.toLowerCase();

    // Se itera sobre las filas de datos y se muestra u oculta cada fila según el texto de búsqueda
    for (let i = 0; i < dataRows.length; i++) {
      const rowData = dataRows[i].innerText.toLowerCase();

      if (rowData.includes(searchText)) {
        dataRows[i].style.display = '';
      } else {
        dataRows[i].style.display = 'none';
      }
    }
  });
</script>

