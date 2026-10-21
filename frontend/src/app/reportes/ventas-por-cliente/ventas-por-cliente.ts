import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, computed, inject } from '@angular/core';
import { PedidosApi } from '../../pedidos/pedidos-api';

@Component({
  imports: [CurrencyPipe, DatePipe],
  selector: 'app-ventas-por-cliente',
  styleUrl: './ventas-por-cliente.scss',
  templateUrl: './ventas-por-cliente.html',
})
export class VentasPorCliente {
  protected readonly reporte = inject(PedidosApi).ventasPorCliente();

  protected readonly totalGeneral = computed(() =>
    this.reporte.hasValue()
      ? this.reporte.value().data.reduce((suma, cliente) => suma + (cliente.total_vendido ?? 0), 0)
      : 0,
  );
}
