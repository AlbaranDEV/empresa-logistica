import { HttpErrorResponse } from '@angular/common/http';

/**
 * Extrae un mensaje legible de un error de la API de Laravel
 * (errores de validación 422, políticas 403, fallos de la transportadora 502, etc.).
 */
export function mensajeDeError(error: unknown): string {
  if (!(error instanceof HttpErrorResponse)) {
    return 'Ocurrió un error inesperado.';
  }

  if (error.status === 0) {
    return 'No hay conexión con el servidor.';
  }

  const errores = error.error?.errors as Record<string, string[]> | undefined;
  const primerError = errores ? Object.values(errores).flat()[0] : undefined;

  return primerError ?? error.error?.message ?? `Error ${error.status} del servidor.`;
}
