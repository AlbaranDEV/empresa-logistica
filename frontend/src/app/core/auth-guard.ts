import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { Auth } from './auth';

export const authGuard: CanActivateFn = () => {
  return inject(Auth).autenticado() || inject(Router).createUrlTree(['/login']);
};

/** Evita mostrar el login a quien ya tiene sesión. */
export const invitadoGuard: CanActivateFn = () => {
  return !inject(Auth).autenticado() || inject(Router).createUrlTree(['/pedidos']);
};
