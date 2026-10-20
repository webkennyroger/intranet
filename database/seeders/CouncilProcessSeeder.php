<?php

namespace Database\Seeders;

use App\Models\CouncilProcess;
use Illuminate\Database\Seeder;

/**
 * Processos SEI reais do Conselho Superior, sem duplicar caso o seeder
 * seja executado mais de uma vez. O link do SEI de cada um fica vazio
 * até ser preenchido no admin — o número do processo só abre o link
 * quando esse campo é cadastrado.
 */
class CouncilProcessSeeder extends Seeder
{
    public function run(): void
    {
        $processes = [
            ['sei_number' => '2025.0.000019186-8', 'subject' => '2º RELATÓRIO – ESTÁGIO PROBATÓRIO – MAXUEL DIAS', 'assigned_to' => 'VINICIUS WILLIAM ISHY FUZARO', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2025.0.000019143-4', 'subject' => '2º RELATÓRIO – ESTÁGIO PROBATÓRIO – LÍGIA NASCIMENTO', 'assigned_to' => 'VINICIUS WILLIAM ISHY FUZARO', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2025.0.000018759-3', 'subject' => '2º RELATÓRIO – ESTÁGIO PROBATÓRIO – ISABELLA CHIMELI', 'assigned_to' => 'LAYSA BITENCOURT PEREIRA', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2025.0.000013471-6', 'subject' => 'ATRIBUIÇÕES DO CONSELHO – ITEM B – ART. 102 LC 80/94', 'assigned_to' => 'CARLOS EDUARDO ROIKA JÚNIOR', 'interested_party' => 'JACQUELINE MARQUES (CHEFE DE GABINETE)'],
            ['sei_number' => '2025.0.000009767-5', 'subject' => 'PROPOSTA DE NOVAS NORMAS PARA NÚCLEOS DE ATUAÇÃO ESTRATÉGICA', 'assigned_to' => 'JACQUELINE GEVIZIER RODRIGUES CISCATO', 'interested_party' => 'SECRETARIA EXECUTIVA'],
            ['sei_number' => '2025.0.000009114-6', 'subject' => 'PEDIDO C – NOVO ÓRGÃO – JUIZADO ESPECIAL DE VÁRZEA GRANDE', 'assigned_to' => 'CARLOS EDUARDO ROIKA JÚNIOR', 'interested_party' => 'DEFENSORES DE VÁRZEA GRANDE'],
            ['sei_number' => '2025.0.000008392-5', 'subject' => 'OFÍCIO Nº 075/2023 – NÚCLEO AGRÁRIO', 'assigned_to' => 'JULIANO BOTELHO DE ARAÚJO', 'interested_party' => 'SR. INÁCIO JOSÉ WERNER, PRESIDENTE DO CEDH/MT'],
            ['sei_number' => '2025.0.000007420-9', 'subject' => 'FOLGAS COMPENSATÓRIAS – ASSESSORES EM PLANTÃO', 'assigned_to' => 'CLAUDINEY SERROU DOS SANTOS', 'interested_party' => 'GRAZIELLI L. ALENCAR / LEANDRO TORRANO'],
            ['sei_number' => '2025.0.000005565-4', 'subject' => 'PEDIDOS A e B – DEFENSORES DE VÁRZEA GRANDE', 'assigned_to' => 'CARLOS EDUARDO ROIKA JÚNIOR', 'interested_party' => 'DEFENSORES DE VÁRZEA GRANDE'],
            ['sei_number' => '2025.0.000003643-9', 'subject' => 'ALTERAÇÃO DAS ATRIBUIÇÕES – 6ª E 7ª DEFENSORIAS – TANGARÁ DA SERRA', 'assigned_to' => 'JÚLIO VICENTE ANDRADE DINIZ', 'interested_party' => 'RENATO H. FERRAREZ E MARIA CECÍLIA A. CUNHA'],
            ['sei_number' => '2025.0.000003520-3', 'subject' => 'ESTÁGIO PROBATÓRIO – 2º RELATÓRIO – BRUNA SAYÃO', 'assigned_to' => 'PAULA FERREIRA FERNANDES', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2025.0.000001924-0', 'subject' => '2º RELATÓRIO – ESTÁGIO PROBATÓRIO – PRISCILA ZART', 'assigned_to' => 'JÚLIO VICENTE ANDRADE DINIZ', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2025.0.000001180-0', 'subject' => 'DISTRIBUIÇÃO DE PROCESSOS – 1ª E 11ª VARAS CRIMINAIS DE CUIABÁ', 'assigned_to' => 'PAULA FERREIRA FERNANDES', 'interested_party' => 'CAIO CEZAR BUIN ZUMIOTI'],
            ['sei_number' => '2024.0.000013002-1', 'subject' => '2º RELATÓRIO – ESTÁGIO PROBATÓRIO – RENATA FERREIRA', 'assigned_to' => 'VINICIUS WILLIAM ISHY FUZARO', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2024.0.000012992-9', 'subject' => '2º RELATÓRIO – ESTÁGIO PROBATÓRIO – CAROLINE SAKAUI', 'assigned_to' => 'JULIANO BOTELHO DE ARAÚJO', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2024.0.000012989-9', 'subject' => '2º RELATÓRIO – ESTÁGIO PROBATÓRIO – CAMILA MAIA', 'assigned_to' => 'JACQUELINE GEVIZIER RODRIGUES CISCATO', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2024.0.000010499-3', 'subject' => 'ATRIBUIÇÕES DO CONSELHO – ITEM A – ART. 102 LC 80/94', 'assigned_to' => 'CARLOS EDUARDO ROIKA JÚNIOR', 'interested_party' => 'JACQUELINE MARQUES (CHEFE DE GABINETE)'],
            ['sei_number' => '2024.0.000007069-0 (SIGILO)', 'subject' => 'RECURSO ADMINISTRATIVO – . O. C SIGILO', 'assigned_to' => 'CLAUDINEY SERROU DOS SANTOS', 'interested_party' => 'ELAYNE CRISTINA MARTINS'],
            ['sei_number' => '2024.0.000006850-4', 'subject' => 'RES. 144/2022 – EXCLUSÃO DE ATOS INICIADOS NO EXPEDIENTE DO PLANTÃO', 'assigned_to' => 'JULIANO BOTELHO DE ARAÚJO', 'interested_party' => 'AMDEP'],
            ['sei_number' => '2024.0.000004307-2', 'subject' => 'PROPOSTA DE ALTERAÇÃO – RESOLUÇÃO 90/2017', 'assigned_to' => 'JULIANO BOTELHO DE ARAÚJO', 'interested_party' => 'DEFENSORES PÚBLICOS (LISTAGEM NOMINAL)'],
            ['sei_number' => '2024.0.000002113-3', 'subject' => 'APURAÇÃO DE CONDUTA – MARCELO ALEXANDRE', 'assigned_to' => 'JACQUELINE GEVIZIER RODRIGUES', 'interested_party' => 'CORREGEDORIA GERAL'],
            ['sei_number' => '2024.0.000001636-9', 'subject' => 'SOLICITA HISTÓRICO DE SUBSÍDIO DOS DEFENSORES PÚBLICOS', 'assigned_to' => 'JANAÍNA YUMI OSAKI', 'interested_party' => 'AMDEP'],
        ];

        foreach ($processes as $process) {
            CouncilProcess::firstOrCreate(
                ['sei_number' => $process['sei_number']],
                [...$process, 'external_url' => null, 'is_published' => true],
            );
        }
    }
}
