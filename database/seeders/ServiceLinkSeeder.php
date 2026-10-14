<?php

namespace Database\Seeders;

use App\Models\ServiceLink;
use Illuminate\Database\Seeder;

/**
 * Garante que os links de serviços padrão da DPEMT já existam na página
 * "Serviços", sem duplicar caso o seeder seja executado mais de uma vez.
 * As URLs ficam como placeholder ("#") até serem preenchidas no admin.
 */
class ServiceLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['title' => 'Portal da Defensora e do Defensor Público', 'icon' => 'display'],
            ['title' => 'Portal da Servidora e do Servidor', 'icon' => 'user'],
            ['title' => 'Sistema Solar', 'icon' => 'gear'],
            ['title' => 'Sistema Administrativo', 'icon' => 'sliders'],
            ['title' => 'Calculadora de Pensão Alimentícia', 'icon' => 'calculator'],
            ['title' => 'Protocolo SEI', 'icon' => 'circle-exclamation'],
            ['title' => 'Vade Meccum', 'icon' => 'book-open'],
            ['title' => 'Pergunte ao Jurídico', 'icon' => 'circle-question'],
            ['title' => 'Gab.IA', 'icon' => 'share-nodes'],
            ['title' => 'DGE', 'icon' => 'clipboard'],
            ['title' => 'Contatos', 'icon' => 'address-book'],
            ['title' => 'Diário Oficial', 'icon' => 'newspaper'],
            ['title' => 'Conta Com a Gente', 'icon' => 'shield'],
            ['title' => 'Sistema de Promoção', 'icon' => 'square-check'],
            ['title' => 'Convênios', 'icon' => 'handshake'],
            ['title' => 'Chamados T.I.', 'icon' => 'computer'],
            ['title' => 'SID', 'icon' => 'scale-balanced'],
            ['title' => 'Café com Jurídico', 'icon' => 'mug-hot'],
            ['title' => 'Sistema de Diárias e Adiantamentos', 'icon' => 'receipt'],
            ['title' => 'Chatbot Maxximobi', 'icon' => 'robot'],
            ['title' => 'Painel de Projetos', 'icon' => 'table-columns'],
            ['title' => 'SECEX', 'icon' => 'laptop'],
            ['title' => 'SIMPE', 'icon' => 'chart-line'],
            ['title' => 'Canal do Escutadoria', 'icon' => 'headset'],
        ];

        foreach ($links as $order => $link) {
            ServiceLink::firstOrCreate(
                ['title' => $link['title']],
                ['icon' => $link['icon'], 'url' => '#', 'order' => $order, 'is_active' => true],
            );
        }
    }
}
