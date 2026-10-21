import { Routes } from '@angular/router';
import { authGuard, invitadoGuard } from './core/auth-guard';

export const routes: Routes = [
  {
    path: 'login',
    title: 'Iniciar sesión',
    canActivate: [invitadoGuard],
    loadComponent: () => import('./auth/login/login').then((m) => m.Login),
  },
  {
    path: '',
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'pedidos' },
      {
        path: 'pedidos',
        title: 'Pedidos',
        loadComponent: () => import('./pedidos/pedidos-list/pedidos-list').then((m) => m.PedidosList),
      },
      {
        path: 'pedidos/nuevo',
        title: 'Nuevo pedido',
        loadComponent: () => import('./pedidos/pedido-form/pedido-form').then((m) => m.PedidoForm),
      },
      {
        path: 'pedidos/:id/editar',
        title: 'Editar pedido',
        loadComponent: () => import('./pedidos/pedido-form/pedido-form').then((m) => m.PedidoForm),
      },
      {
        path: 'reportes/ventas-por-cliente',
        title: 'Ventas por cliente',
        loadComponent: () =>
          import('./reportes/ventas-por-cliente/ventas-por-cliente').then((m) => m.VentasPorCliente),
      },
    ],
  },
  { path: '**', redirectTo: '' },
];
