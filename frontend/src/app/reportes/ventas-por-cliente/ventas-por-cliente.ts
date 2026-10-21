import { CurrencyPipe, DatePipe, PercentPipe } from '@angular/common';
import { Component, computed, inject } from '@angular/core';
import { PedidosApi } from '../../pedidos/pedidos-api';
import { Icon } from '../../shared/icon';

@Component({
  imports: [CurrencyPipe, DatePipe, PercentPipe, Icon],
  selector: 'app-ventas-por-cliente',
  styleUrl: './ventas-por-cliente.scss',
  templateUrl: './ventas-por-cliente.html',
})
export class VentasPorCliente {
  protected readonly reporte = inject(PedidosApi).ventasPorCliente();

  /** Indicadores del mes calculados a partir del ranking. */
  protected readonly resumen = computed(() => {
    const clientes = this.reporte.hasValue() ? this.reporte.value().data : [];
    const total = clientes.reduce((suma, cliente) => suma + (cliente.total_vendido ?? 0), 0);
    const pedidos = clientes.reduce((suma, cliente) => suma + (cliente.cantidad_pedidos ?? 0), 0);

    return {
      total,
      pedidos,
      clientes: clientes.length,
      ticketPromedio: pedidos ? total / pedidos : 0,
      maximo: Math.max(0, ...clientes.map((cliente) => cliente.total_vendido ?? 0)),
    };
  });
}
