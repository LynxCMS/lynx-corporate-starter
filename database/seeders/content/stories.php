<?php

declare(strict_types=1);

/*
 * Case studies, insights and training programs, in English and European Portuguese. Every client,
 * figure and event here is invented.
 */
return [
    'case-studies' => [
        ['name' => 'velaris-motorsport', 'pt_name' => 'velaris-motorsport', 'products' => ['orchestrate'], 'shared' => ['client' => 'Velaris Motorsport', 'stat_value' => '2×'],
            'en' => ['title' => 'Velaris Motorsport writes race-day content with agents', 'summary' => 'Lap data becomes stories for fans minutes after the chequered flag.', 'stat_label' => 'daily active fans in the app'],
            'pt' => ['title' => 'A Velaris Motorsport escreve conteúdos de corrida com agentes', 'summary' => 'Os dados de cada volta tornam-se histórias para os fãs minutos depois da bandeira de xadrez.', 'stat_label' => 'fãs ativos por dia na aplicação']],
        ['name' => 'northwind-footwear', 'pt_name' => 'northwind-footwear', 'products' => ['keyring'], 'shared' => ['client' => 'Northwind Footwear', 'stat_value' => '100%'],
            'en' => ['title' => 'Northwind Footwear closes every critical vulnerability', 'summary' => 'Infrastructure as code, and secrets that never reach a laptop.', 'stat_label' => 'compliance for high and critical findings'],
            'pt' => ['title' => 'A Northwind Footwear resolve todas as vulnerabilidades críticas', 'summary' => 'Infraestrutura como código e segredos que nunca chegam a um portátil.', 'stat_label' => 'conformidade em falhas altas e críticas']],
        ['name' => 'halcyon-health', 'pt_name' => 'halcyon-saude', 'products' => ['pilot', 'streamline'], 'shared' => ['client' => 'Halcyon Health', 'stat_value' => '1 week'],
            'en' => ['title' => 'Halcyon Health makes thirty years of records ready for AI', 'summary' => 'Pilot read the old code; Streamline moved the data while it was still in use.', 'stat_label' => 'to deliver each prototype'],
            'pt' => ['title' => 'A Halcyon Health prepara trinta anos de registos para a IA', 'summary' => 'O Pilot leu o código antigo; o Streamline moveu os dados enquanto estavam em uso.', 'stat_label' => 'para entregar cada protótipo', 'stat_value' => '1 semana']],
        ['name' => 'arcturus-it', 'pt_name' => 'arcturus-ti', 'products' => ['orchestrate', 'ledger'], 'shared' => ['client' => 'Arcturus Systems', 'stat_value' => '86%'],
            'en' => ['title' => 'How Arcturus runs its own help desk on agents', 'summary' => 'Client zero: the tools we sell, used on ourselves first.', 'stat_label' => 'of routine issues solved without a ticket'],
            'pt' => ['title' => 'Como a Arcturus gere o seu próprio apoio técnico com agentes', 'summary' => 'Cliente zero: as ferramentas que vendemos, usadas primeiro em nós.', 'stat_label' => 'dos problemas comuns resolvidos sem pedido']],
    ],
    'insights' => [
        ['name' => 'cost-of-a-breach-2026', 'pt_name' => 'custo-de-uma-violacao-2026', 'topics' => ['security'], 'shared' => ['kind' => 'report', 'date' => '2026-07-29'],
            'en' => ['title' => 'Cost of a Data Breach 2026', 'summary' => 'What a breach costs this year, and the three habits that halve it.'],
            'pt' => ['title' => 'Custo de uma violação de dados 2026', 'summary' => 'Quanto custa uma violação este ano, e os três hábitos que reduzem o custo para metade.']],
        ['name' => 'recovery-you-can-prove', 'pt_name' => 'recuperacao-comprovada', 'topics' => ['security'], 'shared' => ['kind' => 'webinar', 'date' => '2026-11-10'],
            'en' => ['title' => 'Recovery you can actually prove', 'summary' => 'A live session on restoring systems, and showing auditors that you did.'],
            'pt' => ['title' => 'Uma recuperação que consegue provar', 'summary' => 'Uma sessão em direto sobre como repor sistemas e mostrar aos auditores que o fez.']],
        ['name' => 'open-source-security-ai', 'pt_name' => 'seguranca-open-source-ia', 'topics' => ['security', 'ai'], 'shared' => ['kind' => 'report', 'date' => '2026-09-15'],
            'en' => ['title' => 'Rethinking open source security in the AI era', 'summary' => 'Four hundred vulnerabilities found and fixed, and what they had in common.'],
            'pt' => ['title' => 'Repensar a segurança open source na era da IA', 'summary' => 'Quatrocentas vulnerabilidades encontradas e corrigidas, e o que tinham em comum.']],
        ['name' => 'modernizing-core-systems', 'pt_name' => 'modernizar-sistemas-centrais', 'topics' => ['hybrid-cloud'], 'shared' => ['kind' => 'guidebook', 'date' => '2026-06-02'],
            'en' => ['title' => 'Modernizing the systems you cannot switch off', 'summary' => 'A guidebook for changing core systems while the business keeps running on them.'],
            'pt' => ['title' => 'Modernizar os sistemas que não pode desligar', 'summary' => 'Um guia para mudar sistemas centrais enquanto o negócio continua a depender deles.']],
        ['name' => 'state-of-ai-agents', 'pt_name' => 'estado-dos-agentes-ia', 'topics' => ['ai'], 'shared' => ['kind' => 'report', 'date' => '2026-10-01'],
            'en' => ['title' => 'The state of AI agents in production', 'summary' => 'Two thousand companies on what their agents do, and what they are not allowed to.'],
            'pt' => ['title' => 'O estado dos agentes de IA em produção', 'summary' => 'Duas mil empresas sobre o que os seus agentes fazem, e o que não lhes é permitido.']],
        ['name' => 'agents-in-production', 'pt_name' => 'agentes-em-producao', 'topics' => ['ai'], 'shared' => ['kind' => 'webinar', 'date' => '2026-11-24'],
            'en' => ['title' => 'Agents in production, without surprises', 'summary' => 'Budgets, permissions and logs for agents, shown on a real system.'],
            'pt' => ['title' => 'Agentes em produção, sem surpresas', 'summary' => 'Orçamentos, permissões e registos para agentes, num sistema real.']],
    ],
    'training' => [
        ['name' => 'professional', 'pt_name' => 'profissional',
            'en' => ['title' => 'Strengthen your expertise', 'summary' => 'Certifications for architects, developers and administrators.', 'cta_label' => 'Browse courses'],
            'pt' => ['title' => 'Reforce os seus conhecimentos', 'summary' => 'Certificações para arquitetos, programadores e administradores.', 'cta_label' => 'Ver cursos']],
        ['name' => 'foundations', 'pt_name' => 'fundamentos',
            'en' => ['title' => 'Start shaping tomorrow', 'summary' => 'Free foundational courses in AI, data and security, at your own pace.', 'cta_label' => 'Start learning'],
            'pt' => ['title' => 'Comece a construir o futuro', 'summary' => 'Cursos gratuitos de introdução à IA, dados e segurança, ao seu ritmo.', 'cta_label' => 'Começar']],
        ['name' => 'students', 'pt_name' => 'estudantes',
            'en' => ['title' => 'Begin your success journey', 'summary' => 'Pathways for students, from a first course to a first job.', 'cta_label' => 'Find a pathway'],
            'pt' => ['title' => 'Comece o seu percurso', 'summary' => 'Percursos para estudantes, do primeiro curso ao primeiro emprego.', 'cta_label' => 'Encontrar um percurso']],
    ],
];
