import { Component, computed, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Auth } from './core/auth';
import { Icon, NombreIcono } from './shared/icon';

interface ItemMenu {
  ruta: string;
  etiqueta: string;
  icono: NombreIcono;
  exacta: boolean;
}

@Component({
  imports: [RouterOutlet, RouterLink, RouterLinkActive, Icon],
  selector: 'app-root',
  styleUrl: './app.scss',
  templateUrl: './app.html',
})
export class App {
  protected readonly auth = inject(Auth);
  private readonly router = inject(Router);

  protected readonly menu: readonly ItemMenu[] = [
    { ruta: '/pedidos', etiqueta: 'Pedidos', icono: 'package', exacta: true },
    { ruta: '/pedidos/nuevo', etiqueta: 'Nuevo pedido', icono: 'plus', exacta: false },
    { ruta: '/reportes/ventas-por-cliente', etiqueta: 'Ventas por cliente', icono: 'chart', exacta: false },
  ];

  protected readonly iniciales = computed(() =>
    (this.auth.usuario()?.name ?? '')
      .split(' ')
      .map((parte) => parte[0])
      .join('')
      .slice(0, 2)
      .toUpperCase(),
  );

  protected salir(): void {
    this.auth.logout().subscribe(() => this.router.navigate(['/login']));
  }
}
