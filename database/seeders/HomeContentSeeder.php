<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Event;
use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Garante que a página Início (portal de notícias) tenha conteúdo real
 * suficiente pra exibir todas as seções: notícias publicadas, álbuns de
 * fotos, um evento futuro e aniversariantes do mês. Só cria o que faltar —
 * não duplica nada se o seeder rodar de novo com o banco já populado.
 */
class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureArticles();
        $this->ensurePhotoAlbums();
        $this->ensureEvents();
        $this->ensureBirthdays();
    }

    private function ensureArticles(): void
    {
        $target = 7;
        $existing = Article::published()->count();

        if ($existing >= $target) {
            return;
        }

        $author = User::where('email', 'admin@defensoria.am.gov.br')->first() ?? User::factory()->create();
        $categoryIds = Category::pluck('id');

        $articles = [
            [
                'title' => 'Defensoria amplia atendimento e moderniza sistemas nas comarcas do interior',
                'excerpt' => 'Ações integradas visam acelerar a resolução de demandas cíveis e de família, garantindo maior acesso aos assistidos.',
                'body' => '<p>A Defensoria Pública do Estado de Mato Grosso vem ampliando a estrutura de atendimento nas comarcas do interior, com a modernização dos sistemas internos e a capacitação contínua das equipes. A iniciativa busca reduzir o tempo de resposta às demandas da população assistida.</p>',
            ],
            [
                'title' => 'Novo portal de serviços internos otimiza chamados de TI e logística',
                'excerpt' => 'Ferramenta centraliza requisições com acompanhamento em tempo real e relatórios automatizados para gestores.',
                'body' => '<p>O novo portal de serviços internos permite que servidores abram e acompanhem chamados de TI e logística em um só lugar, com notificações automáticas sobre o andamento de cada solicitação.</p>',
            ],
            [
                'title' => 'Inscrições abertas para o curso de atualização em Direito Processual',
                'excerpt' => 'Vagas limitadas para defensores e assessores jurídicos. Aulas síncronas com transmissão via plataforma oficial.',
                'body' => '<p>A ESDEP-MT abriu as inscrições para o curso de atualização em Direito Processual, voltado a defensores e assessores jurídicos. As aulas serão síncronas, com transmissão pela plataforma oficial de capacitação.</p>',
            ],
            [
                'title' => 'Corregedoria-Geral divulga calendário de correições para o próximo semestre',
                'excerpt' => 'Visitas abrangem núcleos da capital e do interior, com foco na qualidade do atendimento ao público.',
                'body' => '<p>A Corregedoria-Geral divulgou o calendário de correições ordinárias para o próximo semestre, contemplando núcleos de atendimento da capital e do interior do estado.</p>',
            ],
            [
                'title' => 'Núcleo de Direitos Humanos lança cartilha sobre acesso à justiça',
                'excerpt' => 'Material traz orientações práticas para a população sobre como buscar atendimento jurídico gratuito.',
                'body' => '<p>O Núcleo de Direitos Humanos lançou uma cartilha com orientações práticas sobre acesso à justiça, voltada à população em situação de vulnerabilidade social.</p>',
            ],
        ];

        for ($i = $existing; $i < $target; $i++) {
            $article = $articles[$i - $existing] ?? $articles[array_rand($articles)];

            $created = Article::factory()->create([
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'body' => $article['body'],
                'author_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subHours($i * 7 + 2),
            ]);

            $created->categories()->attach($categoryIds->isNotEmpty() ? $categoryIds->random() : Category::factory()->create()->id);
        }
    }

    private function ensurePhotoAlbums(): void
    {
        if (PhotoAlbum::where('is_published', true)->count() > 0) {
            return;
        }

        $albums = [
            ['title' => 'Dia da Árvore', 'description' => 'Ação de plantio de mudas na sede administrativa.', 'photos' => 12],
            ['title' => 'Encontro Regional: Edição Rondonópolis', 'description' => 'Integração das equipes do sul do estado.', 'photos' => 28],
            ['title' => 'Inauguração do núcleo de Apiacás', 'description' => 'Entrega do novo núcleo de atendimento.', 'photos' => 15],
        ];

        foreach ($albums as $index => $album) {
            $photoAlbum = PhotoAlbum::create([
                'title' => $album['title'],
                'description' => $album['description'],
                'cover_image_path' => null,
                'event_date' => now()->subDays(($index + 1) * 7),
                'is_published' => true,
            ]);

            for ($p = 1; $p <= $album['photos']; $p++) {
                Photo::factory()->create([
                    'photo_album_id' => $photoAlbum->id,
                    'caption' => "Registro {$p} — {$album['title']}",
                    'order' => $p,
                ]);
            }
        }
    }

    private function ensureEvents(): void
    {
        if (Event::where('is_published', true)->where('starts_at', '>=', now())->count() > 0) {
            return;
        }

        Event::create([
            'title' => 'Debates contemporâneos sobre a guerra às drogas',
            'description' => 'O evento trará discussões aprofundadas sobre políticas públicas, aspectos criminológicos e os impactos nas diretrizes de atuação da Defensoria Pública do Estado de Mato Grosso.',
            'location' => 'Auditório Márcio Vidal — ESDEP-MT',
            'starts_at' => Carbon::parse('next monday')->setTime(9, 0),
            'ends_at' => Carbon::parse('next monday')->setTime(11, 0),
            'is_published' => true,
        ]);
    }

    private function ensureBirthdays(): void
    {
        if (User::whereNotNull('birth_date')->whereMonth('birth_date', now()->month)->count() > 0) {
            return;
        }

        $byDay = [
            3 => 2,
            7 => 1,
            14 => 3,
            20 => 1,
            28 => 2,
        ];

        foreach ($byDay as $day => $count) {
            User::factory()->count($count)->create([
                'birth_date' => now()->setDay(min($day, now()->daysInMonth))->format('Y-m-d'),
            ]);
        }
    }
}
