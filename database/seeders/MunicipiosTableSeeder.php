<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipiosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1) Desactivar comprobación de FK y truncar
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('municipios')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2) Leer el volcado SQL y extraer sólo los INSERT INTO municipios
        $sql = file_get_contents(database_path('seeders/sql/municipios.sql'));

        // Usamos regex para capturar cada INSERT INTO `municipios` ... ;
        preg_match_all('/INSERT INTO `municipios`.*?;/si', $sql, $matches);

        // 3) Ejecutar cada INSERT por separado, evitando el CREATE TABLE
        foreach ($matches[0] as $insert) {
            DB::unprepared($insert);
        }

        // 4) Mensaje de confirmación
        $this->command->info('Tabla municipios sembrada correctamente.');
    }
}
