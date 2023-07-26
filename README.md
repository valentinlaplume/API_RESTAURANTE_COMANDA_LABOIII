# **API de Comanda de Restaurante**

La API de Comanda de Restaurante es una plataforma desarrollada para facilitar la gestión de pedidos, asignación de mesas, control de stock, ventas y proporcionar estadísticas por empleado, entre otras funcionalidades, para un restaurante o establecimiento gastronómico. Esta API está diseñada para ser utilizada por el personal del restaurante, como los mozos, encargados y administradores, con el fin de agilizar y mejorar la operación del negocio.


## **Características principales**
- Gestión de Pedidos: Los mozos pueden realizar pedidos a través de la API, especificando los platos, bebidas o cualquier otro producto del menú que los clientes deseen.
- Asignación de Mesas: La API permite asignar clientes a mesas específicas, lo que facilita el seguimiento de los pedidos y brinda una mejor organización del establecimiento.
- Control de Stock: El sistema lleva un registro del inventario de productos y actualiza automáticamente las existencias después de cada pedido realizado. Así, el personal puede estar al tanto del stock disponible en tiempo real.
- Ventas y Facturación: La API proporciona información sobre las ventas realizadas en un período de tiempo determinado, lo que permite generar reportes y realizar el cálculo de facturación.
- Estadísticas por Empleado: Se puede acceder a estadísticas e informes individuales de cada empleado del restaurante, lo que permite evaluar su rendimiento y contribución al negocio.

## **Requisitos y Tecnologías**
- La API está desarrollada utilizando Slim Framework 4 PHP
- Se requiere una base de datos MySql para almacenar la información relevante sobre los pedidos, mesas, empleados y productos.
- El sistema de autenticación se basa en tokens JWT (JSON Web Tokens) para garantizar la seguridad de las solicitudes a la API.
