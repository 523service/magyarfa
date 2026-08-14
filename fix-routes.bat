@echo off
echo Clearing Laravel caches...
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo.
echo Testing admin route...
php artisan route:list --path=admin
echo.
echo Done! Try accessing /admin now.
pause
