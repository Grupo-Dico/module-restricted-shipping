# GDMexico_RestrictedShipping

## Objetivo
Bloquear el checkout cuando el municipio del envío esté restringido y el carrito contenga productos afectados por alguna regla activa.

## Escalabilidad incluida
El módulo ya queda preparado para:
- Bloquear múltiples municipios desde administración.
- Bloquear por producto con atributo de catálogo.
- Bloquear por categoría desde configuración.
- Bloquear por proveedor logístico desde configuración.
- Activar o desactivar cada regla desde administración.

## Cómo funciona
1. El checkout toma el código postal capturado.
2. Se consulta `LeanCommerce_Sepomex` para obtener el municipio.
3. Se valida si el municipio está en configuración restringida.
4. Se evalúan las reglas activas sobre los productos del carrito:
   - atributo `is_external_carrier_restricted`
   - categorías configuradas
5. Si alguna regla aplica, se bloquea el checkout con mensaje al cliente.

## Validaciones incluidas
- Bloqueo temprano al guardar información de envío.
- Bloqueo backend al intentar colocar la orden.
- Compatible con clientes logueados y guest.
- Revalidación al cambiar dirección.

## Configuración
Ruta en admin:
`Stores > Configuration > Sales > Restricción de Envíos por Municipio`

### Campos principales
- **Habilitar validación**
- **Mensaje al cliente**
- **Municipios restringidos**
- **Bloquear por producto marcado**
- **Bloquear por categoría**
- **Categorías restringidas**
- **Bloquear por proveedor logístico**
- **Proveedores logísticos restringidos**

## Atributos de producto
### 1. `is_external_carrier_restricted`
Tipo Sí/No para marcar productos que no deben enviarse a municipios restringidos.

## Ejemplo de configuración para tu caso actual
- Municipios restringidos: `Chimalhuacán`
- Bloquear por producto marcado: `Sí`

## Ejemplo futuro
- Municipios restringidos:
  - `Chimalhuacán`
  - `Ecatepec`
  - `Nezahualcóyotl`
- Bloquear por producto marcado: `Sí`
- Bloquear por categoría: `Sí`

## Instalación
```bash
bin/magento module:enable GDMexico_RestrictedShipping
bin/magento setup:upgrade
bin/magento cache:flush
```


## Notas sobre atributos

Los atributos del módulo se asignan automáticamente a **todos los attribute sets de producto existentes** durante la instalación/actualización del módulo.

Atributos creados:
- `is_external_carrier_restricted`
