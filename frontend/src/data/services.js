import {
    Home, Sprout, Hammer, ShieldCheck, Truck, PaintBucket,
    Wifi, Wrench, Thermometer, Droplet, LayoutGrid
} from 'lucide-vue-next';

export const services = [
    {
        id: 'nettoyage',
        slug: 'nettoyage-residentiel',
        title: 'Nettoyage Résidentiel',
        shortDesc: 'Service de nettoyage complet pour votre maison.',
        description: 'Offrez à votre intérieur un éclat nouveau avec notre service de nettoyage résidentiel complet. Nos professionnels utilisent des produits écologiques et des techniques éprouvées pour garantir un environnement sain et impeccable.',
        icon: Home,
        color: 'text-blue-600',
        bg: 'bg-blue-50',
        image: 'https://images.unsplash.com/photo-1581578731117-104f8a74695e?q=80&w=2070&auto=format&fit=crop',
        features: [
            'Nettoyage complet des sols (aspirateur, serpillière)',
            'Dépoussiérage de toutes les surfaces',
            'Nettoyage approfondi de la cuisine et des sanitaires',
            'Nettoyage des vitres (intérieur/extérieur)',
            'Désinfection des points de contact'
        ]
    },
    {
        id: 'jardinage',
        slug: 'entretien-jardin',
        title: 'Entretien de Jardin',
        shortDesc: 'Tonte, taille et entretien paysager.',
        description: 'Transformez votre espace extérieur en un véritable havre de paix. Nos jardiniers experts prennent soin de vos plantes, de votre pelouse et de vos arbres pour un jardin toujours impeccable.',
        icon: Sprout,
        color: 'text-green-600',
        bg: 'bg-green-50',
        image: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?q=80&w=2074&auto=format&fit=crop',
        features: [
            'Tonte de pelouse et désherbage',
            'Taille des haies et arbustes',
            'Entretien des massifs de fleurs',
            'Ramassage des feuilles mortes',
            'Arrosage et conseils d\'entretien'
        ]
    },
    {
        id: 'bricolage',
        slug: 'bricolage-domicile',
        title: 'Bricolage à Domicile',
        shortDesc: 'Petites réparations et montages.',
        description: 'Besoin d\'un coup de main pour vos petits travaux ? Nos bricoleurs polyvalents sont là pour vous aider, du montage de meubles à la fixation d\'étagères.',
        icon: Hammer,
        color: 'text-amber-600',
        bg: 'bg-amber-50',
        image: 'https://images.unsplash.com/photo-1505798577917-a651a5d60bbd?q=80&w=2070&auto=format&fit=crop',
        features: [
            'Montage et démontage de meubles',
            'Fixation d\'étagères, cadres et tringles',
            'Petits travaux de peinture et retouches',
            'Réparation de portes et fenêtres',
            'Installation d\'équipements simples'
        ]
    },
    {
        id: 'securite',
        slug: 'securite-surveillance',
        title: 'Sécurité & Surveillance',
        shortDesc: 'Installation de systèmes et gardiennage.',
        description: 'Protégez ce qui compte le plus pour vous. Nos experts en sécurité installent et entretiennent vos systèmes de surveillance pour une tranquillité d\'esprit totale.',
        icon: ShieldCheck,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50',
        image: 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?q=80&w=2070&auto=format&fit=crop',
        features: [
            'Installation de caméras de surveillance',
            'Pose d\'alarmes anti-intrusion',
            'Maintenance des systèmes de sécurité',
            'Audit de sécurité de votre domicile',
            'Conseils en protection et prévention'
        ]
    },
    {
        id: 'demenagement',
        slug: 'demenagement',
        title: 'Déménagement',
        shortDesc: 'Aide au déménagement et transport.',
        description: 'Changez de domicile sans stress. Nos équipes de déménageurs professionnels s\'occupent de tout, de l\'emballage au transport, avec soin et efficacité.',
        icon: Truck,
        color: 'text-orange-600',
        bg: 'bg-orange-50',
        image: 'https://images.unsplash.com/photo-1600585152220-90363fe7e115?q=80&w=2070&auto=format&fit=crop',
        features: [
            'Manutention et transport de meubles',
            'Emballage et protection des objets fragiles',
            'Montage et démontage de mobilier',
            'Location de camion avec chauffeur',
            'Mise en carton et déballage'
        ]
    },
    {
        id: 'renovation',
        slug: 'renovation',
        title: 'Rénovation',
        shortDesc: 'Peinture, plomberie et électricité.',
        description: 'Donnez vie à vos projets de rénovation. Nos artisans qualifiés réalisent vos travaux de peinture, plomberie et électricité pour un résultat professionnel et durable.',
        icon: PaintBucket,
        color: 'text-purple-600',
        bg: 'bg-purple-50',
        image: 'https://images.unsplash.com/photo-1581578731117-104f8a74695e?q=80&w=2070&auto=format&fit=crop',
        features: [
            'Travaux de peinture intérieure et extérieure',
            'Rénovation de salle de bain et cuisine',
            'Mise aux normes électriques',
            'Installation sanitaire et plomberie',
            'Pose de revêtements de sol (carrelage, parquet)'
        ]
    }
];
