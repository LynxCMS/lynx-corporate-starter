<?php

declare(strict_types=1);

/*
 * Topics and products, in English and European Portuguese. Arcturus Systems, its products, clients
 * and figures are invented.
 */
return [
    'topics' => [
        ['name' => 'ai', 'pt_name' => 'ia', 'en' => ['title' => 'Artificial intelligence', 'summary' => 'Agents, assistants and the data they run on.'], 'pt' => ['title' => 'Inteligência artificial', 'summary' => 'Agentes, assistentes e os dados de que precisam.']],
        ['name' => 'security', 'pt_name' => 'seguranca', 'en' => ['title' => 'Security', 'summary' => 'Identities, secrets and the vulnerabilities between them.'], 'pt' => ['title' => 'Segurança', 'summary' => 'Identidades, segredos e as vulnerabilidades entre eles.']],
        ['name' => 'hybrid-cloud', 'pt_name' => 'cloud-hibrida', 'en' => ['title' => 'Hybrid cloud', 'summary' => 'Workloads that run where they make most sense.'], 'pt' => ['title' => 'Cloud híbrida', 'summary' => 'Cargas de trabalho onde fazem mais sentido.']],
    ],
    'products' => [
        [
            'name' => 'streamline', 'pt_name' => 'streamline', 'topics' => ['ai', 'hybrid-cloud'],
            'shared' => ['stat_value' => '35%'],
            'en' => ['title' => 'Arcturus Streamline', 'claim' => 'Real-time data, without a second copy of it.', 'summary' => 'One streaming layer for every system that produces events, and every model that needs them.', 'stat_label' => 'lower infrastructure cost', 'body' => '<p>Streamline joins event streams and tables in one place, so analytics and AI read the same data as it happens.</p>'],
            'pt' => ['claim' => 'Dados em tempo real, sem uma segunda cópia.', 'summary' => 'Uma camada de streaming para todos os sistemas que produzem eventos e todos os modelos que precisam deles.', 'stat_label' => 'menos custos de infraestrutura', 'body' => '<p>O Streamline junta fluxos de eventos e tabelas num só lugar, para que a análise e a IA leiam os mesmos dados no momento.</p>'],
        ],
        [
            'name' => 'pilot', 'pt_name' => 'pilot', 'topics' => ['ai'],
            'shared' => ['stat_value' => '50%'],
            'en' => ['title' => 'Arcturus Pilot', 'claim' => 'A coding partner that reads the whole repository.', 'summary' => 'Pilot plans changes across services, writes the tests first and explains every step.', 'stat_label' => 'fewer code dependencies after a migration', 'body' => '<p>Pilot works in the editor and in reviews, and knows the difference between code that compiles and code that ships.</p>'],
            'pt' => ['claim' => 'Um parceiro de programação que lê o repositório inteiro.', 'summary' => 'O Pilot planeia alterações entre serviços, escreve primeiro os testes e explica cada passo.', 'stat_label' => 'menos dependências após uma migração', 'body' => '<p>O Pilot trabalha no editor e nas revisões, e conhece a diferença entre código que compila e código que chega a produção.</p>'],
        ],
        [
            'name' => 'connect', 'pt_name' => 'connect', 'topics' => ['hybrid-cloud'],
            'shared' => ['stat_value' => '80%'],
            'en' => ['title' => 'Arcturus Connect', 'claim' => 'Every application, one integration platform.', 'summary' => 'APIs, events and file transfers designed, run and watched from one place.', 'stat_label' => 'less time spent on file integrations', 'body' => '<p>Connect turns the spreadsheet of interfaces nobody owns into a catalogue everybody can search.</p>'],
            'pt' => ['claim' => 'Todas as aplicações, uma plataforma de integração.', 'summary' => 'APIs, eventos e transferências de ficheiros desenhados, executados e vigiados num só lugar.', 'stat_label' => 'menos tempo em integrações de ficheiros', 'body' => '<p>O Connect transforma a folha de cálculo de interfaces sem dono num catálogo que todos podem pesquisar.</p>'],
        ],
        [
            'name' => 'orchestrate', 'pt_name' => 'orchestrate', 'topics' => ['ai'],
            'shared' => ['stat_value' => '3×'],
            'en' => ['title' => 'Arcturus Orchestrate', 'claim' => 'Agents that work together, and answer to you.', 'summary' => 'Build, govern and watch AI agents from any vendor in one control room.', 'stat_label' => 'more requests handled by the same team', 'body' => '<p>Orchestrate gives every agent an owner, a budget and a log, before it gets access to anything.</p>'],
            'pt' => ['claim' => 'Agentes que trabalham em conjunto e respondem a si.', 'summary' => 'Crie, governe e acompanhe agentes de IA de qualquer fornecedor numa só sala de controlo.', 'stat_label' => 'mais pedidos tratados pela mesma equipa', 'body' => '<p>O Orchestrate dá a cada agente um responsável, um orçamento e um registo, antes de ter acesso a seja o que for.</p>'],
        ],
        [
            'name' => 'ledger', 'pt_name' => 'ledger', 'topics' => ['ai', 'hybrid-cloud'],
            'shared' => ['stat_value' => '70%'],
            'en' => ['title' => 'Arcturus Ledger', 'claim' => 'Know what AI costs, and what it earns.', 'summary' => 'Cloud and AI spend traced to the teams and products that cause it.', 'stat_label' => 'faster approval of spending requests', 'body' => '<p>Ledger answers the question finance asks every quarter, before finance asks it.</p>'],
            'pt' => ['claim' => 'Saiba quanto custa a IA e quanto rende.', 'summary' => 'Gastos de cloud e IA associados às equipas e produtos que os geram.', 'stat_label' => 'aprovação mais rápida de pedidos de despesa', 'body' => '<p>O Ledger responde à pergunta que a área financeira faz todos os trimestres, antes de ela a fazer.</p>'],
        ],
        [
            'name' => 'keyring', 'pt_name' => 'keyring', 'topics' => ['security'],
            'shared' => ['stat_value' => '50%'],
            'en' => ['title' => 'Arcturus Keyring', 'claim' => 'Secrets and machine identities, handled.', 'summary' => 'Short-lived credentials for people, services and agents, rotated without a maintenance window.', 'stat_label' => 'less time managing secrets', 'body' => '<p>Keyring replaces the password in the config file with one that expires before anyone can copy it.</p>'],
            'pt' => ['claim' => 'Segredos e identidades de máquinas, tratados.', 'summary' => 'Credenciais de curta duração para pessoas, serviços e agentes, renovadas sem janelas de manutenção.', 'stat_label' => 'menos tempo a gerir segredos', 'body' => '<p>O Keyring substitui a palavra-passe no ficheiro de configuração por uma que expira antes que alguém a copie.</p>'],
        ],
    ],
];
