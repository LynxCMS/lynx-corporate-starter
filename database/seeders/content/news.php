<?php

declare(strict_types=1);

/*
 * News, hero slides and promos, in English and European Portuguese. Every event, partner and
 * figure here is invented. Dates are relative to the day the seeders run, so the news stays new.
 */
return [
    'news' => [
        ['name' => 'q3-results', 'pt_name' => 'resultados-t3', 'days_ago' => 1, 'topics' => [],
            'en' => ['title' => 'Arcturus reports third-quarter results', 'summary' => 'Software revenue up 11%, led by Orchestrate and Keyring.'],
            'pt' => ['title' => 'Arcturus apresenta resultados do terceiro trimestre', 'summary' => 'Receitas de software sobem 11%, lideradas pelo Orchestrate e pelo Keyring.']],
        ['name' => 'velaris-renewal', 'pt_name' => 'renovacao-velaris', 'days_ago' => 3, 'topics' => ['ai'],
            'en' => ['title' => 'Velaris Motorsport renews its partnership for five seasons', 'summary' => 'Agents will now write commentary in eleven languages.'],
            'pt' => ['title' => 'A Velaris Motorsport renova a parceria por cinco temporadas', 'summary' => 'Os agentes vão passar a escrever comentários em onze línguas.']],
        ['name' => 'agent-identity', 'pt_name' => 'identidade-de-agentes', 'days_ago' => 5, 'topics' => ['security', 'ai'],
            'en' => ['title' => 'Keyring gives every AI agent an identity of its own', 'summary' => 'Agents get short-lived credentials, the same way services do.'],
            'pt' => ['title' => 'O Keyring dá a cada agente de IA uma identidade própria', 'summary' => 'Os agentes recebem credenciais de curta duração, tal como os serviços.']],
        ['name' => 'emea-incident-study', 'pt_name' => 'estudo-incidentes-emea', 'days_ago' => 8, 'topics' => ['security'],
            'en' => ['title' => 'Study: half of AI incidents in Europe start with a leaked key', 'summary' => 'The fix is rarely new technology. It is shorter-lived secrets.'],
            'pt' => ['title' => 'Estudo: metade dos incidentes de IA na Europa começa com uma chave exposta', 'summary' => 'A solução raramente é tecnologia nova. São segredos que duram menos.']],
        ['name' => 'nanosheet-research', 'pt_name' => 'investigacao-nanofolhas', 'days_ago' => 12, 'topics' => [],
            'en' => ['title' => 'Arcturus Research and partners stack chips a nanometre closer', 'summary' => 'A new packaging method cuts the distance data travels between layers.'],
            'pt' => ['title' => 'A Arcturus Research e parceiros aproximam chips um nanómetro', 'summary' => 'Um novo método de encapsulamento reduz a distância que os dados percorrem entre camadas.']],
        ['name' => 'connect-file-gateway', 'pt_name' => 'gateway-de-ficheiros', 'days_ago' => 16, 'topics' => ['hybrid-cloud'],
            'en' => ['title' => 'Connect adds a gateway for file transfers', 'summary' => 'The nightly batch job now has a dashboard.'],
            'pt' => ['title' => 'O Connect ganha um gateway para transferências de ficheiros', 'summary' => 'O processo noturno passa a ter um painel.']],
        ['name' => 'streamline-ga', 'pt_name' => 'streamline-disponivel', 'days_ago' => 21, 'topics' => ['ai'],
            'en' => ['title' => 'Streamline is generally available', 'summary' => 'On every major cloud, and on the machines you already own.'],
            'pt' => ['title' => 'O Streamline está disponível para todos', 'summary' => 'Em todas as grandes clouds e nas máquinas que já tem.']],
        ['name' => 'open-source-fixes', 'pt_name' => 'correcoes-open-source', 'days_ago' => 30, 'topics' => ['security'],
            'en' => ['title' => '400 open source vulnerabilities found and fixed', 'summary' => 'Our researchers sent the patches upstream before announcing anything.'],
            'pt' => ['title' => '400 vulnerabilidades open source encontradas e corrigidas', 'summary' => 'Os nossos investigadores enviaram as correções aos projetos antes de anunciar.']],
    ],
    // In tree order. The summit slide stops showing two weeks after the seeders run.
    'slides' => [
        ['name' => 'summit', 'link' => '/insights/agents-in-production/', 'unpublish_in_days' => 14,
            'en' => ['title' => 'Arcturus Summit 2026: the leaders putting AI to work', 'eyebrow' => 'Live from Lisbon', 'summary' => 'Two days of keynotes and labs on agents, data and security. Watch every session live.', 'cta_label' => 'Explore the event'],
            'pt' => ['title' => 'Arcturus Summit 2026: quem está a pôr a IA a trabalhar', 'eyebrow' => 'Em direto de Lisboa', 'summary' => 'Dois dias de apresentações e laboratórios sobre agentes, dados e segurança. Veja todas as sessões em direto.', 'cta_label' => 'Conhecer o evento']],
        ['name' => 'orchestrate-launch', 'link' => '/products/orchestrate/',
            'en' => ['title' => 'Agents that work together, and answer to you', 'eyebrow' => 'New: Arcturus Orchestrate', 'summary' => 'Every agent gets an owner, a budget and a log, before it gets access to anything.', 'cta_label' => 'Meet Orchestrate'],
            'pt' => ['title' => 'Agentes que trabalham em conjunto e respondem a si', 'eyebrow' => 'Novo: Arcturus Orchestrate', 'summary' => 'Cada agente tem um responsável, um orçamento e um registo, antes de ter acesso a seja o que for.', 'cta_label' => 'Conhecer o Orchestrate']],
        ['name' => 'breach-report', 'link' => '/insights/cost-of-a-breach-2026/',
            'en' => ['title' => 'What a data breach costs in 2026', 'eyebrow' => 'Cybersecurity Month', 'summary' => 'And the three habits that cut that cost in half.', 'cta_label' => 'Read the report'],
            'pt' => ['title' => 'Quanto custa uma violação de dados em 2026', 'eyebrow' => 'Mês da Cibersegurança', 'summary' => 'E os três hábitos que reduzem esse custo para metade.', 'cta_label' => 'Ler o relatório']],
    ],
    'promos' => [
        ['name' => 'pilot-trial', 'kind' => 'trial', 'link' => '/products/pilot/',
            'en' => ['title' => 'Try Pilot free for 30 days', 'summary' => 'A coding partner for your whole repository, with no card required.', 'cta_label' => 'Start the trial'],
            'pt' => ['title' => 'Experimente o Pilot grátis durante 30 dias', 'summary' => 'Um parceiro de programação para todo o repositório, sem cartão.', 'cta_label' => 'Começar a avaliação']],
        ['name' => 'developer-tools', 'kind' => 'tool', 'link' => '/products/connect/',
            'en' => ['title' => 'Self-service tools for developers', 'summary' => 'AI, integration, observability and infrastructure, ready when you are.', 'cta_label' => 'Explore the tools'],
            'pt' => ['title' => 'Ferramentas self-service para programadores', 'summary' => 'IA, integração, observabilidade e infraestrutura, prontas quando estiver.', 'cta_label' => 'Ver as ferramentas']],
        ['name' => 'partner-program', 'kind' => 'program', 'link' => '/training/professional/',
            'en' => ['title' => 'Grow with the Arcturus Partner Program', 'summary' => 'Training, sales support and a share of every deal you bring.', 'cta_label' => 'Become a partner'],
            'pt' => ['title' => 'Cresça com o Programa de Parceiros Arcturus', 'summary' => 'Formação, apoio comercial e uma parte de cada negócio que trouxer.', 'cta_label' => 'Tornar-se parceiro']],
    ],
];
