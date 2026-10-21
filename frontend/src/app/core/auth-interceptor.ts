import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { Auth } from './auth';

/**
 * Adjunta el token de Sanctum a las peticiones de la API y
 * cierra la sesión local si el servidor responde 401.
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(Auth);
  const router = inject(Router);
  const token = auth.token();

  const peticion = req.url.startsWith('/api/')
    ? req.clone({
        setHeaders: { Accept: 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
      })
    : req;

  return next(peticion).pipe(
    catchError((error: unknown) => {
      if (error instanceof HttpErrorResponse && error.status === 401 && token) {
        auth.limpiar();
        router.navigate(['/login']);
      }
      return throwError(() => error);
    }),
  );
};
