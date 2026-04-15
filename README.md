# GDMexico_RestrictedShipping

## Objetivo
Bloquear el flujo de envío y colocación de pedido cuando:

1. El municipio resuelto a partir del código postal está configurado como restringido.
2. El carrito contiene productos marcados con el atributo `is_external_carrier_restricted`.

## Alcance actual
Este módulo implementa actualmente:

- Validación por municipio restringido.
- Validación por atributo de producto marcado.
- Bloqueo en carrito.
- Bloqueo al guardar dirección de envío.
- Bloqueo al estimar métodos de envío.
- Bloqueo antes de colocar la orden.
- Validación para cliente logueado y guest.

## No implementado actualmente
Este módulo no implementa hoy:

- Reglas por categoría.
- Reglas por proveedor logístico.
- Configuración de carriers restringidos.
- Configuración de categorías restringidas.

## Configuración
Ruta en administración:

`Stores > Configuration > Sales > Restricción de Envíos por Municipio`

### Campos disponibles
- Habilitar validación
- Mensaje al cliente
- Municipios restringidos
- Bloquear por producto marcado

## Atributo de producto
### is_external_carrier_restricted
Atributo booleano para indicar que un producto no debe permitirse en municipios restringidos.

## Instalación
```bash
bin/magento module:enable GDMexico_RestrictedShipping
bin/magento setup:upgrade
bin/magento cache:flush