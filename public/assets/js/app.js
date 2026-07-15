/* ========================================
   21-LMS Dashboard - Dynamic Data
   ========================================
   Change the values below to update the page content.
   The structure is controlled by home.html,
   the styles are controlled by style.css.
*/

const data = {
    user: {
        name: "test.user",
        role: "Core program",
        level: 7,
        xp: 730,
        xpToNextLevel: 1000,
        totalXp: 4634,
        avatarUrl: "", // leave empty to show fallback icon
    },

    profile: {
        skills: [
            { name: "C", xp: 1105 },
            { name: "C++", xp: 920 },
            { name: "OOP", xp: 1350 },
            { name: "SQL", xp: 1280 },
            { name: "DB & Data", xp: 1150 },
            { name: "Web", xp: 1420 },
            { name: "HTML/CSS", xp: 1480 },
            { name: "Frontend", xp: 1390 },
            { name: "JavaScript", xp: 1300 },
            { name: "TypeScript", xp: 980 },
            { name: "Structured programming", xp: 1050 },
            { name: "Types and data structures", xp: 880 },
            { name: "Software architecture", xp: 760 },
            { name: "Information Security", xp: 640 },
            { name: "Graphics", xp: 520 },
            { name: "Algorithms", xp: 690 },
            { name: "Backend", xp: 580 },
            { name: "Mobile", xp: 430 },
            { name: "Swift", xp: 310 },
            { name: "Java", xp: 470 },
            { name: "Kotlin", xp: 290 },
            { name: "Go", xp: 350 },
            { name: "C#", xp: 270 },
            { name: "Math", xp: 410 },
            { name: "ML & AI", xp: 330 },
            { name: "Python", xp: 460 },
            { name: "QA", xp: 390 },
            { name: "Analysis", xp: 560 },
            { name: "Code review", xp: 720 },
            { name: "Leadership", xp: 510 },
            { name: "Team work", xp: 990 },
            { name: "Company experience", xp: 680 },
            { name: "Functional programming", xp: 540 },
            { name: "Parallel computing", xp: 410 },
            { name: "Electronics", xp: 280 },
            { name: "Network & system administration", xp: 620 },
            { name: "Shell/Bash", xp: 740 },
            { name: "DevOps", xp: 450 },
            { name: "Linux", xp: 590 },
        ],
        peerFeedback: [
            { label: "Interested", icon: "interested", value: "4 / 4" },
            { label: "Nice", icon: "nice", value: "4 / 4" },
            { label: "Punctual", icon: "punctual", value: "4 / 4" },
            { label: "Rigorous", icon: "rigorous", value: "4 / 4" },
        ],
        peerReviews: 2,
        email: "test.user@student.21-school.ru",
        location: "21 Test",
        status: "Out of campus",
        penalties: [],
        tribeContribution: {
            name: "Computers",
            points: 2,
            text: "The contribution is 2 tribe points",
        },
        xpGraph: [
            { date: "10.03.2023", xp: 0 },
            { date: "11.03.2023", xp: 0 },
            { date: "12.03.2023", xp: 2105 },
        ],
        badges: [
            { title: "Success", icon: `<svg viewBox="0 0 40 40" width="40" height="40" shape-rendering="crispEdges">
        <rect x="12" y="4" width="16" height="4" fill="#f9a8d4"/>
        <rect x="8" y="8" width="24" height="8" fill="#f9a8d4"/>
        <rect x="12" y="16" width="16" height="4" fill="#f9a8d4"/>
        <rect x="12" y="20" width="16" height="4" fill="#fbbf24"/>
        <rect x="16" y="24" width="8" height="4" fill="#fbbf24"/>
        <rect x="16" y="28" width="8" height="4" fill="#f59e0b"/>
      </svg>` },
            { title: "Will you be my peer?", icon: `<svg viewBox="0 0 40 40" width="40" height="40" shape-rendering="crispEdges">
        <rect x="8" y="4" width="4" height="4" fill="#9ca3af"/>
        <rect x="28" y="4" width="4" height="4" fill="#9ca3af"/>
        <rect x="4" y="8" width="32" height="24" rx="4" fill="#9ca3af"/>
        <rect x="12" y="14" width="4" height="4" fill="#1f2937"/>
        <rect x="24" y="14" width="4" height="4" fill="#1f2937"/>
        <rect x="16" y="22" width="8" height="4" fill="#e5e7eb"/>
        <rect x="18" y="30" width="4" height="4" fill="#ef4444"/>
      </svg>` },
            { title: "Happy Halloween!", icon: `<svg viewBox="0 0 40 40" width="40" height="40" shape-rendering="crispEdges">
        <rect x="16" y="4" width="8" height="4" fill="#22c55e"/>
        <rect x="8" y="8" width="24" height="24" rx="6" fill="#f97316"/>
        <rect x="12" y="14" width="4" height="4" fill="#3f1607"/>
        <rect x="24" y="14" width="4" height="4" fill="#3f1607"/>
        <rect x="14" y="24" width="12" height="4" fill="#3f1607"/>
      </svg>` },
        ],
        logtime: {
            week: "13 March – 19 March 2023",
            days: [
                { day: "MON", campus: 0, imac: 5 },
                { day: "TUE", campus: 0, imac: 8 },
                { day: "WED", campus: 0, imac: 9 },
                { day: "THU", campus: 0, imac: 7 },
                { day: "FRI", campus: 0, imac: 6 },
                { day: "SAT", campus: 0, imac: 0 },
                { day: "SUN", campus: 0, imac: 0 },
            ],
        },
        logtimeOniMac: {
            average: 7,
            week: "13 March – 19 March 2023",
        },
    },

    stats: [
        { label: "Computers ↗ 2", sub: "Tribe Tournament", highlight: true },
        { label: "Out of campus", sub: "21 Test", highlight: true },
        { label: "No deadline", sub: "\u00A0", highlight: false },
        { label: "210 ¢", sub: "Coins", highlight: false },
        { label: "5", sub: "Peer Review points", highlight: false },
        { label: "5", sub: "Code Review points", highlight: false },
    ],

    events: {
        empty: true,
        title: "No upcoming events",
        text: "You don't have any events available, try coming back later",
    },

    notifications: {
        filters: ["all", "profile", "projects", "awards"],
        activeFilter: "all",
        items: [
            {
                id: 1,
                filter: "projects",
                dateGroup: "13 MARCH",
                title: "Project Peer Review starts soon!",
                body: '<strong>SimpleBashUtils</strong> project review with <strong>test-review2</strong> will start on <strong>13.03.2023, 15:00</strong>',
                time: "13 March, 14:45",
            },
            {
                id: 2,
                filter: "projects",
                dateGroup: "13 MARCH",
                title: "Project assignment",
                body: 'You have been assigned <strong>SimpleBashUtils</strong> project for an extra review on <strong>March 13, 15:00</strong>',
                time: "13 March, 14:33",
            },
            {
                id: 3,
                filter: "projects",
                dateGroup: "13 MARCH",
                title: "Project Peer Review starts soon!",
                body: '<strong>SimpleBashUtils</strong> project review with <strong>test-review1</strong> will start on <strong>13.03.2023, 14:30</strong>',
                time: "13 March, 14:15",
            },
            {
                id: 4,
                filter: "projects",
                dateGroup: "13 MARCH",
                title: "Project assignment",
                body: 'You have been assigned <strong>SimpleBashUtils</strong> project for an extra review on <strong>March 13, 14:30</strong>',
                time: "13 March, 14:07",
            },
            {
                id: 5,
                filter: "projects",
                dateGroup: "12 MARCH",
                title: "Project Presentation starts soon!",
                body: '<strong>s21_matrix</strong> project presentation with <strong>test.user</strong> will start on <strong>12.03.2023, 15:30</strong>',
                time: "12 March, 15:15",
            },
            {
                id: 6,
                filter: "projects",
                dateGroup: "12 MARCH",
                title: "Project Presentation assignment",
                body: 'You have been assigned for an extra <strong>s21_matrix</strong> project presentation on <strong>March 12, 15:30</strong>',
                time: "12 March, 15:14",
            },
            {
                id: 7,
                filter: "profile",
                dateGroup: "11 MARCH",
                title: "Profile update reminder",
                body: 'Please review and update your <strong>profile information</strong> to keep it up to date.',
                time: "11 March, 10:22",
            },
        ],
    },

    agenda: {
        dates: [
            {
                date: "15 November",
                items: [
                    {
                        start: "15:15",
                        end: "15:45",
                        title: "Participant project presentation",
                        desc: "Project «SimpleBashUtils» with <strong>r.developer</strong>",
                    },
                ],
            },
            {
                date: "Today, 18 March",
                items: [],
                emptyText: "There are no events for today",
            },
        ],
    },

    badges: [
        {
            title: "Success",
            sub: "No rank",
            icon: `<svg viewBox="0 0 40 40" width="40" height="40" shape-rendering="crispEdges">
        <rect x="12" y="4" width="16" height="4" fill="#f9a8d4"/>
        <rect x="8" y="8" width="24" height="8" fill="#f9a8d4"/>
        <rect x="12" y="16" width="16" height="4" fill="#f9a8d4"/>
        <rect x="12" y="20" width="16" height="4" fill="#fbbf24"/>
        <rect x="16" y="24" width="8" height="4" fill="#fbbf24"/>
        <rect x="16" y="28" width="8" height="4" fill="#f59e0b"/>
      </svg>`,
        },
        {
            title: "Will you be my peer?",
            sub: "No rank",
            heart: true,
            icon: `<svg viewBox="0 0 40 40" width="40" height="40" shape-rendering="crispEdges">
        <rect x="8" y="4" width="4" height="4" fill="#9ca3af"/>
        <rect x="28" y="4" width="4" height="4" fill="#9ca3af"/>
        <rect x="4" y="8" width="32" height="24" rx="4" fill="#9ca3af"/>
        <rect x="12" y="14" width="4" height="4" fill="#1f2937"/>
        <rect x="24" y="14" width="4" height="4" fill="#1f2937"/>
        <rect x="16" y="22" width="8" height="4" fill="#e5e7eb"/>
        <rect x="18" y="30" width="4" height="4" fill="#ef4444"/>
      </svg>`,
        },
        {
            title: "Happy Halloween!",
            sub: "No rank",
            icon: `<svg viewBox="0 0 40 40" width="40" height="40" shape-rendering="crispEdges">
        <rect x="16" y="4" width="8" height="4" fill="#22c55e"/>
        <rect x="8" y="8" width="24" height="24" rx="6" fill="#f97316"/>
        <rect x="12" y="14" width="4" height="4" fill="#3f1607"/>
        <rect x="24" y="14" width="4" height="4" fill="#3f1607"/>
        <rect x="14" y="24" width="12" height="4" fill="#3f1607"/>
      </svg>`,
        },
    ],

    tournaments: [
        {
            id: "t1",
            name: "Waterbears",
            membersCount: 143,
            points: 6908,
            accent: "#0ea5e9",
            icon: `<svg viewBox="0 0 64 64" width="34" height="34"><circle cx="32" cy="32" r="26" fill="#0ea5e9"/><circle cx="24" cy="26" r="5" fill="#fff"/><circle cx="40" cy="26" r="5" fill="#fff"/><circle cx="24" cy="26" r="2" fill="#0f172a"/><circle cx="40" cy="26" r="2" fill="#0f172a"/><path d="M20 40c0 8 5 12 12 12s12-4 12-12" fill="#fff" opacity="0.4"/></svg>`,
            top: ["u1", "u2", "u3", "u4", "u5"],
            last: "u64",
        },
        {
            id: "t2",
            name: "Slugs",
            membersCount: 144,
            points: 6976,
            accent: "#22c55e",
            icon: `<svg viewBox="0 0 64 64" width="34" height="34"><rect x="12" y="24" width="40" height="24" rx="12" fill="#22c55e"/><circle cx="24" cy="34" r="4" fill="#fff"/><circle cx="40" cy="34" r="4" fill="#fff"/><path d="M16 48c4 6 28 6 32 0" stroke="#15803d" stroke-width="3" fill="none"/></svg>`,
            top: ["u6", "u7", "u8", "u9", "u10"],
            last: "u65",
        },
    ],
};

const usersData = {
    u1: {
        name: "gruntmet@student.21-school.ru",
        shortName: "gruntmet",
        role: "Survival camp",
        level: 4,
        xp: 680,
        xpToNextLevel: 1000,
        totalXp: 1712,
        avatarUrl: "",
        tribePoints: 225,
        rank: 1,
        location: "21 Moscow",
        status: "Out of campus",
        email: "gruntmet@student.21-school.ru",
        peerReviews: 31,
        peerFeedback: [
            { label: "Interested", icon: "interested", value: "4 / 4" },
            { label: "Nice", icon: "nice", value: "4 / 4" },
            { label: "Punctual", icon: "punctual", value: "4 / 4" },
            { label: "Rigorous", icon: "rigorous", value: "4 / 4" },
        ],
        skills: [
            { name: "C", xp: 650 }, { name: "C++", xp: 580 }, { name: "OOP", xp: 720 },
            { name: "SQL", xp: 600 }, { name: "DB & Data", xp: 640 }, { name: "Web", xp: 780 },
            { name: "HTML/CSS", xp: 820 }, { name: "Frontend", xp: 790 }, { name: "JavaScript", xp: 760 },
            { name: "TypeScript", xp: 540 }, { name: "Structured programming", xp: 610 },
            { name: "Types and data structures", xp: 520 }, { name: "Software architecture", xp: 480 },
            { name: "Information Security", xp: 390 }, { name: "Graphics", xp: 310 },
            { name: "Algorithms", xp: 420 }, { name: "Backend", xp: 360 }, { name: "Mobile", xp: 250 },
            { name: "Swift", xp: 180 }, { name: "Java", xp: 290 }, { name: "Kotlin", xp: 170 },
            { name: "Go", xp: 220 }, { name: "C#", xp: 150 }, { name: "Math", xp: 260 },
            { name: "ML & AI", xp: 200 }, { name: "Python", xp: 280 }, { name: "QA", xp: 230 },
            { name: "Analysis", xp: 340 }, { name: "Code review", xp: 430 }, { name: "Leadership", xp: 300 },
            { name: "Team work", xp: 560 }, { name: "Company experience", xp: 380 },
            { name: "Functional programming", xp: 320 }, { name: "Parallel computing", xp: 250 },
            { name: "Electronics", xp: 170 }, { name: "Network & system administration", xp: 350 },
            { name: "Shell/Bash", xp: 410 }, { name: "DevOps", xp: 270 }, { name: "Linux", xp: 330 },
        ],
        xpGraph: [
            { date: "10.03.2023", xp: 0 },
            { date: "11.03.2023", xp: 450 },
            { date: "12.03.2023", xp: 1260 },
        ],
        logtime: {
            week: "13 March – 19 March 2023",
            days: [
                { day: "MON", campus: 0, imac: 4 },
                { day: "TUE", campus: 0, imac: 6 },
                { day: "WED", campus: 0, imac: 7 },
                { day: "THU", campus: 0, imac: 5 },
                { day: "FRI", campus: 0, imac: 4 },
                { day: "SAT", campus: 0, imac: 0 },
                { day: "SUN", campus: 0, imac: 0 },
            ],
        },
        logtimeOniMac: { average: 5, week: "13 March – 19 March 2023" },
        tribeContribution: { name: "Waterbears", points: 225, text: "The contribution is 225 tribe points" },
        badges: [
            { title: "Success", icon: data.profile.badges[0].icon },
            { title: "Will you be my peer?", icon: data.profile.badges[1].icon, heart: true },
            { title: "Happy Halloween!", icon: data.profile.badges[2].icon },
        ],
    },
    u2: { name: "tangleto@student.21-school.ru", shortName: "tangleto", role: "Core program", level: 2, xp: 340, xpToNextLevel: 1000, totalXp: 890, tribePoints: 162, rank: 2, location: "21 Test", status: "Out of campus", email: "tangleto@student.21-school.ru", peerReviews: 12, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.6) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 4, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Waterbears", points: 162, text: "The contribution is 162 tribe points" }, badges: data.profile.badges },
    u3: { name: "whentalb@student.21-school.ru", shortName: "whentalb", role: "Core program", level: 3, xp: 520, xpToNextLevel: 1000, totalXp: 1240, tribePoints: 162, rank: 2, location: "21 Test", status: "Out of campus", email: "whentalb@student.21-school.ru", peerReviews: 18, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.7) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 6, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Waterbears", points: 162, text: "The contribution is 162 tribe points" }, badges: data.profile.badges },
    u4: { name: "tracymar@student.21-school.ru", shortName: "tracymar", role: "Core program", level: 4, xp: 610, xpToNextLevel: 1000, totalXp: 1480, tribePoints: 159, rank: 3, location: "21 Test", status: "Out of campus", email: "tracymar@student.21-school.ru", peerReviews: 22, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.8) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 5, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Waterbears", points: 159, text: "The contribution is 159 tribe points" }, badges: data.profile.badges },
    u5: { name: "goghorhi@student.21-school.ru", shortName: "goghorhi", role: "Core program", level: 2, xp: 290, xpToNextLevel: 1000, totalXp: 760, tribePoints: 153, rank: 4, location: "21 Test", status: "Out of campus", email: "goghorhi@student.21-school.ru", peerReviews: 9, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.5) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 3, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Waterbears", points: 153, text: "The contribution is 153 tribe points" }, badges: data.profile.badges },
    u64: { name: "kimberep@student.21-school.ru", shortName: "kimberep", role: "Core program", level: 0, xp: 80, xpToNextLevel: 1000, totalXp: 120, tribePoints: 1, rank: 64, location: "21 Test", status: "Out of campus", email: "kimberep@student.21-school.ru", peerReviews: 1, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.15) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 1, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Waterbears", points: 1, text: "The contribution is 1 tribe point" }, badges: data.profile.badges },
    u6: { name: "steelyso@student.21-school.ru", shortName: "steelyso", role: "Core program", level: 5, xp: 740, xpToNextLevel: 1000, totalXp: 1920, tribePoints: 268, rank: 1, location: "21 Test", status: "Out of campus", email: "steelyso@student.21-school.ru", peerReviews: 35, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.9) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 7, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Slugs", points: 268, text: "The contribution is 268 tribe points" }, badges: data.profile.badges },
    u7: { name: "tsobor-arina", shortName: "tsobor-arina", role: "Master", level: 8, xp: 880, xpToNextLevel: 1000, totalXp: 5200, tribePoints: 240, rank: 2, location: "21 Test", status: "Out of campus", email: "tsobor-arina@student.21-school.ru", peerReviews: 42, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 1.1) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 8, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Slugs", points: 240, text: "The contribution is 240 tribe points" }, badges: data.profile.badges },
    u8: { name: "v-ivanova", shortName: "v-ivanova", role: "Master", level: 6, xp: 720, xpToNextLevel: 1000, totalXp: 3100, tribePoints: 210, rank: 3, location: "21 Test", status: "Out of campus", email: "v-ivanova@student.21-school.ru", peerReviews: 28, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.85) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 6, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Slugs", points: 210, text: "The contribution is 210 tribe points" }, badges: data.profile.badges },
    u9: { name: "r-developer", shortName: "r-developer", role: "Core program", level: 3, xp: 430, xpToNextLevel: 1000, totalXp: 1150, tribePoints: 180, rank: 4, location: "21 Test", status: "Out of campus", email: "r-developer@student.21-school.ru", peerReviews: 16, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.65) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 4, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Slugs", points: 180, text: "The contribution is 180 tribe points" }, badges: data.profile.badges },
    u10: { name: "test-review1", shortName: "test-review1", role: "Core program", level: 1, xp: 210, xpToNextLevel: 1000, totalXp: 540, tribePoints: 150, rank: 5, location: "21 Test", status: "Out of campus", email: "test-review1@student.21-school.ru", peerReviews: 7, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.4) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 2, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Slugs", points: 150, text: "The contribution is 150 tribe points" }, badges: data.profile.badges },
    u65: { name: "lastmember@student.21-school.ru", shortName: "lastmember", role: "Core program", level: 0, xp: 50, xpToNextLevel: 1000, totalXp: 80, tribePoints: 1, rank: 65, location: "21 Test", status: "Out of campus", email: "lastmember@student.21-school.ru", peerReviews: 0, peerFeedback: data.profile.peerFeedback, skills: data.profile.skills.map((s) => ({ ...s, xp: Math.round(s.xp * 0.1) })), xpGraph: data.profile.xpGraph, logtime: data.profile.logtime, logtimeOniMac: { average: 0, week: "13 March – 19 March 2023" }, tribeContribution: { name: "Slugs", points: 1, text: "The contribution is 1 tribe point" }, badges: data.profile.badges },
};

/* ========================================
   Helpers
   ======================================== */

function bindText(selector, value) {
    document.querySelectorAll(`[data-bind="${selector}"]`).forEach((el) => {
        if (value !== undefined && value !== null) {
            el.textContent = value;
        }
    });
}

function animateLevelText(level, targetPercent, duration = 1200) {
    const elements = document.querySelectorAll('[data-bind="levelText"]');
    if (!elements.length) return;

    const startTime = performance.now();

    function step(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // easeOutQuart
        const ease = 1 - Math.pow(1 - progress, 4);
        const currentPercent = Math.round(targetPercent * ease);

        elements.forEach((el) => {
            el.textContent = `lvl ${level}: ${currentPercent}%`;
        });

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            elements.forEach((el) => el.classList.add("level-filled"));
        }
    }

    requestAnimationFrame(step);
}

function setProgress(percent) {
    const bar = document.querySelector('[data-bind="levelProgress"]');
    if (!bar) return;
    // CSS animation reads this custom property
    bar.style.setProperty("--progress-width", `${percent}%`);

    bar.classList.remove("progress-filled");
    // Wait for the fill + shine animation to finish, then add glow
    setTimeout(() => {
        bar.classList.add("progress-filled");
    }, 1600);
}

function setProfileProgress(percent) {
    const bar = document.querySelector('[data-bind="profileProgress"]');
    if (!bar) return;
    bar.style.setProperty("--progress-width", `${percent}%`);
}

function renderAvatar(url, fallbackName) {
    const img = document.querySelector('[data-bind="avatar"]');
    const fallback = document.querySelector('[data-bind="avatarFallback"]');
    if (!img || !fallback) return;

    if (url) {
        img.src = url;
        img.alt = fallbackName;
        img.style.display = "block";
        fallback.style.display = "none";
    } else {
        img.style.display = "none";
        fallback.style.display = "flex";
    }
}

function renderStats(stats) {
    const container = document.querySelector('[data-bind="statsList"]');
    if (!container) return;

    container.innerHTML = stats
        .map(
            (stat) => `
      <div class="stat-item">
        <h3>${escapeHtml(stat.label)}</h3>
        <p class="${stat.highlight ? "highlight" : "muted"}">${escapeHtml(stat.sub)}</p>
      </div>
    `
        )
        .join("");
}

function renderEventsCard(events) {
    const container = document.querySelector('[data-bind="eventsCard"]');
    if (!container) return;

    if (events.empty) {
        container.innerHTML = `
      <div class="empty-title">${escapeHtml(events.title)}</div>
      <p class="empty-text">${escapeHtml(events.text)}</p>
    `;
    } else {
        container.innerHTML = `
      <ul class="event-list">
        ${(events.items || [])
            .map(
                (item) => `
          <li class="event-item">
            <div class="event-title">${escapeHtml(item.title)}</div>
            <div class="event-meta">${escapeHtml(item.meta)}</div>
          </li>
        `
            )
            .join("")}
      </ul>
    `;
    }
}

function renderAgendaCard(agenda) {
    const container = document.querySelector('[data-bind="agendaCard"]');
    if (!container) return;

    container.innerHTML = agenda.dates
        .map((date) => {
            const itemsHtml =
                date.items && date.items.length
                    ? date.items
                        .map(
                            (item) => `
            <div class="agenda-item">
              <div class="agenda-time">
                <div class="start">${escapeHtml(item.start)}</div>
                <span class="end">${escapeHtml(item.end)}</span>
              </div>
              <div class="agenda-bar"></div>
              <div>
                <div class="agenda-title">${escapeHtml(item.title)}</div>
                <div class="agenda-desc">${item.desc}</div>
              </div>
            </div>
          `
                        )
                        .join("")
                    : `<p class="empty-text">${escapeHtml(date.emptyText || "No events")}</p>`;

            return `
        <div class="agenda-date">${escapeHtml(date.date)}</div>
        ${itemsHtml}
      `;
        })
        .join("");
}

function renderBadgesCard(badges) {
    const container = document.querySelector('[data-bind="badgesCard"]');
    if (!container) return;

    container.innerHTML = badges
        .map(
            (badge) => `
      <div class="badge-card">
        <div class="badge-icon">${badge.icon}</div>
        <div class="badge-info">
          <h3>${escapeHtml(badge.title)}</h3>
          ${badge.heart ? '<span class="badge-heart">❤️</span>' : ""}
          <p class="muted">${escapeHtml(badge.sub)}</p>
        </div>
      </div>
    `
        )
        .join("");
}

function escapeHtml(text) {
    if (text === undefined || text === null) return "";
    const div = document.createElement("div");
    div.textContent = String(text);
    return div.innerHTML;
}

/* ========================================
   Calendar page
   ======================================== */

(function initCalendarScope() {
    /* ========================================
       Calendar page — UI ONLY.
       ALL business logic is SERVER-SIDE (Laravel CalendarController):
       tabs are links, week nav are links, forms are native POST/DELETE.
       JS here only opens/closes modals and pre-fills hidden date/time
       inputs from the clicked grid cell. No fetch, no validation.
       ======================================== */
    "use strict";

    var calendarPage = document.querySelector(".calendar-page");
    if (!calendarPage) return; // not the calendar page

    /* ---- helpers ---- */
    function formatDate(dateStr) {
        var d = new Date(dateStr + "T00:00:00");
        return d.toLocaleDateString("en-GB", { day: "numeric", month: "long" });
    }

    function addMinutes(timeStr, mins) {
        var parts = timeStr.split(":").map(Number);
        var total = parts[0] * 60 + parts[1] + mins;
        var h = Math.floor(total / 60) % 24;
        var m = total % 60;
        return String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0");
    }

    function openBackdrop(el) {
        if (!el) return;
        el.hidden = false;
        el.setAttribute("aria-hidden", "false");
    }

    function closeBackdrop(el) {
        if (!el) return;
        el.hidden = true;
        el.setAttribute("aria-hidden", "true");
    }

    /* ---- modal refs ---- */
    var createBackdrop = document.getElementById("calendarModalBackdrop");
    var slotInfoBackdrop = document.getElementById("calendarSlotInfoBackdrop");
    var cancelBackdrop = document.getElementById("calendarCancelBackdrop");
    var eventBackdrop  = document.getElementById("calendarEventBackdrop");

    var modalTitle = document.getElementById("calendarModalTitle");
    var modalTime  = document.getElementById("calendarModalTime");
    var toggleSlot  = document.querySelector('.toggle-btn[data-type="slot"]');
    var toggleEvent = document.querySelector('.toggle-btn[data-type="event"]');
    var slotForm  = document.getElementById("slotForm");
    var eventForm = document.getElementById("eventForm");

    /* ---- generic close wiring for every calendar modal ---- */
    [
        [createBackdrop, "calendarModalClose", null],
        [slotInfoBackdrop, "calendarSlotInfoClose", null],
        [cancelBackdrop, "calendarCancelClose", "calendarCancelKeep"],
        [eventBackdrop, "calendarEventClose", null],
    ].forEach(function (cfg) {
        var backdrop = cfg[0];
        if (!backdrop) return;
        var closeBtn = cfg[1] ? document.getElementById(cfg[1]) : null;
        var keepBtn  = cfg[2] ? document.getElementById(cfg[2]) : null;
        if (closeBtn) closeBtn.addEventListener("click", function () { closeBackdrop(backdrop); });
        if (keepBtn)  keepBtn.addEventListener("click", function () { closeBackdrop(backdrop); });
        backdrop.addEventListener("click", function (e) {
            if (e.target === backdrop) closeBackdrop(backdrop);
        });
    });

    document.addEventListener("keydown", function (e) {
        if (e.key !== "Escape") return;
        [createBackdrop, slotInfoBackdrop, cancelBackdrop, eventBackdrop].forEach(function (b) {
            if (b && !b.hidden) closeBackdrop(b);
        });
    });

    /* ---- slot <-> event visual toggle (forms are separate; JS only swaps them) ---- */
    function setMode(mode) {
        if (toggleSlot)  toggleSlot.classList.toggle("active", mode === "slot");
        if (toggleEvent) toggleEvent.classList.toggle("active", mode === "event");
        if (slotForm)  slotForm.hidden  = mode !== "slot";
        if (eventForm) eventForm.hidden = mode !== "event";
        if (modalTitle) modalTitle.textContent = mode === "event" ? "New event" : "Peer Review slot";
    }

    if (toggleSlot)  toggleSlot.addEventListener("click", function () { setMode("slot"); });
    if (toggleEvent) toggleEvent.addEventListener("click", function () { setMode("event"); });

    /* ---- grid cell clicks: open the right modal, pre-fill hidden inputs ---- */
    var grid = document.getElementById("calendarGrid");
    if (grid) {
        grid.addEventListener("click", function (e) {
            var cell = e.target.closest(".grid-cell");
            if (!cell) return;

            var date = cell.dataset.date;
            var time = cell.dataset.time;
            var when = formatDate(date) + ", " + time;

            /* my own slot (cancellable, >24h left) -> cancel confirmation.
               If the system already booked it, show WHO it was booked for. */
            if (cell.dataset.cancelAction && cancelBackdrop) {
                var cancelForm = document.getElementById("calendarCancelForm");
                if (cancelForm) cancelForm.setAttribute("action", cell.dataset.cancelAction);
                var cancelTime = document.getElementById("calendarCancelTime");
                if (cancelTime) {
                    cancelTime.textContent = when + (cell.dataset.bookedBy
                        ? " \u2014 booked by the system for " + cell.dataset.bookedBy
                        : "");
                }
                openBackdrop(cancelBackdrop);
                return;
            }

            /* own slot that cannot be cancelled (<24h) or is booked by the
               system -> read-only info modal (no manual booking exists). */
            if ((cell.dataset.cancelLocked || cell.dataset.bookedBy !== undefined) && slotInfoBackdrop) {
                var infoTime = document.getElementById("calendarSlotInfoTime");
                if (infoTime) infoTime.textContent = when;
                var infoNote = document.getElementById("calendarSlotInfoNote");
                if (infoNote) {
                    var notes = [];
                    if (cell.dataset.bookedBy) notes.push("Booked by the system for: " + cell.dataset.bookedBy + ".");
                    if (cell.dataset.cancelLocked) notes.push("Cannot be cancelled less than 24 hours before it starts.");
                    infoNote.textContent = notes.join(" ") || "Your Peer Review slot.";
                }
                openBackdrop(slotInfoBackdrop);
                return;
            }

            /* existing event -> navigate to the server-rendered
               event details page (public.events.show) */
            if (cell.dataset.eventUrl) {
                window.location.href = cell.dataset.eventUrl;
                return;
            }

            /* fallback: event without a URL -> read-only modal */
            if (cell.dataset.eventTitle && eventBackdrop) {
                var evTitle = document.getElementById("calendarEventTitle");
                if (evTitle) evTitle.textContent = cell.dataset.eventTitle;
                var evTime = document.getElementById("calendarEventTime");
                if (evTime) evTime.textContent = when + (cell.dataset.eventEnd ? " \u2013 " + cell.dataset.eventEnd : "");
                var evDesc = document.getElementById("calendarEventDesc");
                if (evDesc) {
                    var parts = [];
                    if (cell.dataset.eventDesc) parts.push(cell.dataset.eventDesc);
                    if (cell.dataset.eventAuthor) parts.push("By " + cell.dataset.eventAuthor);
                    evDesc.textContent = parts.join(" \u2014 ");
                }
                openBackdrop(eventBackdrop);
                return;
            }

            /* booked cell without actions: nothing to do */
            if (cell.classList.contains("cell-booked") || cell.classList.contains("cell-slot") || cell.classList.contains("cell-event")) {
                return;
            }

            /* empty cell -> create modal; pre-fill BOTH native forms */
            if (!createBackdrop) return;

            if (modalTime) modalTime.textContent = when;

            var slotDate = document.getElementById("slotDate");
            if (slotDate) slotDate.value = date;
            var slotFrom = document.getElementById("slotFrom");
            if (slotFrom) slotFrom.value = time;
            var slotTo = document.getElementById("slotTo");
            if (slotTo) slotTo.value = addMinutes(time, 15);

            var eventDate = document.getElementById("eventDate");
            if (eventDate) eventDate.value = date;
            var eventStart = document.getElementById("eventStart");
            if (eventStart) eventStart.value = time;
            var eventEnd = document.getElementById("eventEnd");
            if (eventEnd) eventEnd.value = addMinutes(time, 15);

            setMode("slot");
            openBackdrop(createBackdrop);
        });

        /* keep the event form times in sync when the slot selects change */
        var slotFromSel = document.getElementById("slotFrom");
        var slotToSel = document.getElementById("slotTo");
        function syncEventTimes() {
            var es = document.getElementById("eventStart");
            var ee = document.getElementById("eventEnd");
            if (es && slotFromSel) es.value = slotFromSel.value;
            if (ee && slotToSel) ee.value = slotToSel.value;
        }
        if (slotFromSel) slotFromSel.addEventListener("change", syncEventTimes);
        if (slotToSel) slotToSel.addEventListener("change", syncEventTimes);
    }

    /* ---- flash toast: close button + auto-hide (visual only) ---- */
    var flash = document.getElementById("calendarFlash");
    if (flash) {
        var flashClose = flash.querySelector(".pd-toast-close");
        if (flashClose) flashClose.addEventListener("click", function () { flash.classList.remove("show"); });
        setTimeout(function () { flash.classList.remove("show"); }, 6000);
    }
})();

/* ========================================
   Activities overlay
   ======================================== */

function initTribesTabs() {
    const tabs = document.querySelectorAll(".tribes-tab");
    const panels = document.querySelectorAll(".tribes-panel");
    const titleEl = document.querySelector('[data-bind="tribePageTitle"]');
    if (!tabs.length || !panels.length) return;

    const titles = {
        tournament: "Test",
        "my-tribe": "Computers",
    };

    function switchTab(target) {
        tabs.forEach((tab) => tab.classList.toggle("active", tab.dataset.tab === target));
        panels.forEach((panel) => panel.classList.toggle("active", panel.dataset.panel === target));
        if (titleEl && titles[target]) {
            titleEl.textContent = titles[target];
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => switchTab(tab.dataset.tab));
    });
}

/* ========================================
   Notifications overlay
   ======================================== */

function renderNotificationsList(notifications, activeFilter, activeId) {
    const container = document.querySelector('[data-bind="notificationsList"]');
    if (!container) return;

    const items =
        activeFilter === "all"
            ? notifications.items
            : notifications.items.filter((item) => item.filter === activeFilter);

    if (!items.length) {
        container.innerHTML = '<p class="empty-text" style="padding: 24px 8px; text-align: center;">No notifications</p>';
        return;
    }

    const grouped = items.reduce((acc, item) => {
        if (!acc[item.dateGroup]) acc[item.dateGroup] = [];
        acc[item.dateGroup].push(item);
        return acc;
    }, {});

    container.innerHTML = Object.entries(grouped)
        .map(
            ([date, groupItems]) => `
      <div class="notifications-date">${escapeHtml(date)}</div>
      ${groupItems
                .map(
                    (item) => `
        <article class="notification-item${item.id === activeId ? " active" : ""}" data-notification-id="${item.id}">
          <h3 class="notification-title">${escapeHtml(item.title)}</h3>
          <p class="notification-body">${item.body}</p>
          <time class="notification-time">${escapeHtml(item.time)}</time>
        </article>
      `
                )
                .join("")}
    `
        )
        .join("");
}

function initNotifications() {
    const toggles = document.querySelectorAll(".notifications-toggle");
    const overlay = document.getElementById("notificationsOverlay");
    if (!toggles.length || !overlay) return;

    let activeId = null;
    let activeFilter = data.notifications.activeFilter;
    let lastFocusedElement = null;

    function setOpen(open) {
        const isOpen = document.body.classList.contains("notifications-open");
        if (open === isOpen) return;

        if (open) {
            lastFocusedElement = document.activeElement;
            renderNotificationsList(data.notifications, activeFilter, activeId);
        }

        document.body.classList.toggle("notifications-open", open);
        document.body.classList.toggle("user-menu-open", !open);
        toggles.forEach((t) => t.classList.toggle("active", open));
        toggles.forEach((t) => t.setAttribute("aria-expanded", String(open)));
        overlay.setAttribute("aria-hidden", String(!open));

        if (!open && lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }

    toggles.forEach((toggle) => {
        toggle.addEventListener("click", (e) => {
            e.preventDefault();
            setOpen(!document.body.classList.contains("notifications-open"));
        });
    });

    const closeBtn = overlay.querySelector(".notifications-close");
    if (closeBtn) {
        closeBtn.addEventListener("click", () => setOpen(false));
    }

    overlay.addEventListener("click", (e) => {
        if (e.target === overlay) setOpen(false);
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && document.body.classList.contains("notifications-open")) {
            setOpen(false);
        }
    });

    const filters = overlay.querySelectorAll(".notifications-filter");
    filters.forEach((filter) => {
        filter.addEventListener("click", () => {
            activeFilter = filter.dataset.filter;
            filters.forEach((f) => f.classList.toggle("active", f.dataset.filter === activeFilter));
            renderNotificationsList(data.notifications, activeFilter, activeId);
        });
    });

    const list = overlay.querySelector(".notifications-list");
    if (list) {
        list.addEventListener("click", (e) => {
            const item = e.target.closest(".notification-item");
            if (!item) return;
            activeId = Number(item.dataset.notificationId);
            renderNotificationsList(data.notifications, activeFilter, activeId);
        });
    }
}

const HEADER_OVERLAY_BODY_CLASSES = ["activities-open", "projects-open"];

function initHeaderOverlay(config) {
    const toggles = document.querySelectorAll(config.toggleSelector);
    const overlay = document.getElementById(config.overlayId);
    if (!toggles.length || !overlay) return;

    const bodyClass = config.bodyClass;
    const itemBtns = overlay.querySelectorAll(".activity-btn");

    let lastFocusedElement = null;

    function setOpen(open) {
        const isOpen = document.body.classList.contains(bodyClass);
        if (open === isOpen) return;

        if (open) {
            // Close any other header overlay that may be open
            HEADER_OVERLAY_BODY_CLASSES.forEach((cls) => {
                if (cls !== bodyClass) document.body.classList.remove(cls);
            });
            lastFocusedElement = document.activeElement;
        }

        document.body.classList.toggle(bodyClass, open);
        toggles.forEach((t) => t.classList.toggle("active", open));
        toggles.forEach((t) => t.setAttribute("aria-expanded", String(open)));
        overlay.setAttribute("aria-hidden", String(!open));

        if (!open && lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }

    toggles.forEach((toggle) => {
        toggle.setAttribute("aria-haspopup", "true");
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-controls", config.overlayId);

        toggle.addEventListener("click", (e) => {
            e.preventDefault();
            setOpen(!document.body.classList.contains(bodyClass));
        });
    });

    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("aria-labelledby", config.titleId);
    overlay.setAttribute("aria-hidden", "true");

    const titleEl = overlay.querySelector(".activities-info h2");
    if (titleEl && !titleEl.id) {
        titleEl.id = config.titleId;
    }

    const closeBtn = overlay.querySelector(".activities-close");
    if (closeBtn) {
        closeBtn.addEventListener("click", () => setOpen(false));
    }

    overlay.addEventListener("click", (e) => {
        if (e.target === overlay) setOpen(false);
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && document.body.classList.contains(bodyClass)) {
            setOpen(false);
        }
    });

    itemBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            itemBtns.forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");
            // Links navigate naturally; buttons can be wired by the user manually.
        });
    });
}

function initActivities() {
    initHeaderOverlay({
        toggleSelector: ".activities-toggle",
        overlayId: "activitiesOverlay",
        bodyClass: "activities-open",
        titleId: "activitiesTitle",
    });
}

function initProjectsOverlay() {
    initHeaderOverlay({
        toggleSelector: ".projects-nav-toggle",
        overlayId: "projectsOverlay",
        bodyClass: "projects-open",
        titleId: "projectsOverlayTitle",
    });
}

function initProjectsPage() {
    /* Tabs are real server links now (?tab=...) — the active state comes
       from the server. No JS panel switching, no fake invites. UI only:
       optional group toggles. */
    document.querySelectorAll(".prj-group-toggle").forEach((btn) => {
        btn.addEventListener("click", () => {
            const expanded = btn.getAttribute("aria-expanded") === "true";
            btn.setAttribute("aria-expanded", String(!expanded));
        });
    });
}

function initProjectDetailsPage() {
    const page = document.querySelector(".pd-page");
    if (!page) return;

    /* All business logic (subscribe, submit for review) is server-side via
       native forms. JS below is pure UI. */

    /* ---------- Copy git link ---------- */
    document.querySelectorAll("[data-copy-text]").forEach((copyBtn) => {
        copyBtn.addEventListener("click", () => {
            const url = copyBtn.dataset.copyText || "";
            if (url && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).catch(() => {});
            }
            const prev = copyBtn.textContent;
            copyBtn.textContent = "Copied!";
            setTimeout(() => {
                copyBtn.textContent = prev;
            }, 1500);
        });
    });

    /* ---------- Statistics collapse ---------- */
    document.querySelectorAll(".pd-stats-toggle").forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const expanded = toggle.getAttribute("aria-expanded") === "true";
            toggle.setAttribute("aria-expanded", String(!expanded));
        });
    });

    /* ---------- Toast close ---------- */
    document.querySelectorAll(".pd-toast .pd-toast-close").forEach((btnClose) => {
        btnClose.addEventListener("click", () => {
            btnClose.closest(".pd-toast").classList.remove("show");
        });
    });
}

/* ========================================
   User menu overlay
   ======================================== */

function initUserMenu() {
    const toggles = document.querySelectorAll(".user-menu-toggle");
    const overlay = document.getElementById("userMenuOverlay");
    if (!toggles.length || !overlay) return;

    let lastFocusedElement = null;

    function setOpen(open) {
        const isOpen = document.body.classList.contains("user-menu-open");
        if (open === isOpen) return;

        if (open) {
            lastFocusedElement = document.activeElement;
        }

        document.body.classList.toggle("user-menu-open", open);
        toggles.forEach((t) => t.classList.toggle("active", open));
        toggles.forEach((t) => t.setAttribute("aria-expanded", String(open)));
        overlay.setAttribute("aria-hidden", String(!open));

        if (!open && lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }

    toggles.forEach((toggle) => {
        toggle.setAttribute("aria-haspopup", "true");
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-controls", "userMenuOverlay");

        toggle.addEventListener("click", (e) => {
            e.preventDefault();
            setOpen(!document.body.classList.contains("user-menu-open"));
        });
    });

    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("aria-labelledby", "userMenuTitle");
    overlay.setAttribute("aria-hidden", "true");

    overlay.addEventListener("click", (e) => {
        if (e.target === overlay) setOpen(false);
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && document.body.classList.contains("user-menu-open")) {
            setOpen(false);
        }
    });

    const items = overlay.querySelectorAll(".user-menu-item");
    items.forEach((item) => {
        item.addEventListener("click", () => {
            items.forEach((i) => i.classList.remove("active"));
            item.classList.add("active");
        });
    });
}

/* ========================================
   Profile page
   ======================================== */

function renderSkillsRadar(skills) {
    const container = document.querySelector('[data-bind="skillsRadar"]');
    if (!container) return;

    const maxXp = 1500;
    const normalizedSkills = skills.map((skill) => ({
        ...skill,
        value: skill.xp / maxXp,
    }));

    const size = 560;
    const center = size / 2;
    const radius = 160;
    const total = normalizedSkills.length;
    const angleStep = (Math.PI * 2) / total;

    function point(value, index) {
        const angle = index * angleStep - Math.PI / 2;
        const r = radius * value;
        return [center + r * Math.cos(angle), center + r * Math.sin(angle)];
    }

    const gridRings = [0.25, 0.5, 0.75, 1];
    const rings = gridRings
        .map((ratio) => {
            const pts = Array.from({ length: total }, (_, i) => {
                const [x, y] = point(ratio, i);
                return `${x},${y}`;
            }).join(" ");
            return `<polygon points="${pts}" fill="none" stroke="#e5e7eb" stroke-width="1" />`;
        })
        .join("");

    const axes = normalizedSkills
        .map((skill, i) => {
            const [x, y] = point(1, i);
            return `<line class="radar-axis" data-skill="${escapeHtml(skill.name)}" data-xp="${skill.xp}" x1="${center}" y1="${center}" x2="${x}" y2="${y}" stroke="#e5e7eb" stroke-width="10" stroke-linecap="round" opacity="0" />`;
        })
        .join("");

    const visibleAxes = normalizedSkills
        .map((_, i) => {
            const [x, y] = point(1, i);
            return `<line x1="${center}" y1="${center}" x2="${x}" y2="${y}" stroke="#e5e7eb" stroke-width="1" />`;
        })
        .join("");

    const dataPoints = normalizedSkills
        .map((skill, i) => point(skill.value, i).join(","))
        .join(" ");

    const pointCircles = normalizedSkills
        .map((skill, i) => {
            const [x, y] = point(skill.value, i);
            return `<circle class="radar-point" data-skill="${escapeHtml(skill.name)}" data-xp="${skill.xp}" cx="${x}" cy="${y}" r="5" fill="#8bea9a" stroke="#ffffff" stroke-width="2" style="cursor: pointer;" />`;
        })
        .join("");

    const labels = normalizedSkills
        .map((skill, i) => {
            const [x, y] = point(1.28, i);
            let anchor = "middle";
            if (x > center + 5) anchor = "start";
            else if (x < center - 5) anchor = "end";
            return `<text x="${x}" y="${y}" text-anchor="${anchor}" font-size="9" fill="#6b7280" font-weight="600">${escapeHtml(skill.name)}</text>`;
        })
        .join("");

    container.innerHTML = `
    <svg viewBox="0 0 ${size} ${size}" width="100%" height="100%">
      ${rings}
      ${visibleAxes}
      ${axes}
      <polygon points="${dataPoints}" fill="rgba(139, 234, 154, 0.25)" stroke="#8bea9a" stroke-width="2" />
      ${pointCircles}
      ${labels}
    </svg>
    <div class="radar-tooltip"></div>
  `;

    const tooltip = container.querySelector(".radar-tooltip");

    function showTooltip(e, name, xp) {
        tooltip.textContent = `${name} ${xp}`;
        tooltip.classList.add("visible");
        moveTooltip(e);
    }

    function moveTooltip(e) {
        const rect = container.getBoundingClientRect();
        const x = e.clientX - rect.left + 12;
        const y = e.clientY - rect.top - 12;
        tooltip.style.left = `${x}px`;
        tooltip.style.top = `${y}px`;
    }

    function hideTooltip() {
        tooltip.classList.remove("visible");
    }

    container.querySelectorAll(".radar-axis, .radar-point").forEach((el) => {
        el.addEventListener("mouseenter", (e) => {
            showTooltip(e, el.dataset.skill, el.dataset.xp);
            el.style.opacity = el.classList.contains("radar-axis") ? "0.25" : "1";
            if (el.classList.contains("radar-point")) {
                el.setAttribute("r", "7");
            }
        });
        el.addEventListener("mousemove", moveTooltip);
        el.addEventListener("mouseleave", () => {
            hideTooltip();
            el.style.opacity = el.classList.contains("radar-axis") ? "0" : "1";
            if (el.classList.contains("radar-point")) {
                el.setAttribute("r", "5");
            }
        });
    });
}

function renderPeerFeedback(feedback) {
    const container = document.querySelector('[data-bind="peerFeedback"]');
    if (!container) return;

    const icons = {
        interested: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
        nice: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>`,
        punctual: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
        rigorous: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>`,
    };

    container.innerHTML = feedback
        .map(
            (item) => `
      <div class="peer-feedback-row">
        <div class="peer-feedback-label">
          <span class="peer-feedback-icon">${icons[item.icon] || ""}</span>
          ${escapeHtml(item.label)}
        </div>
        <span class="peer-feedback-value">${escapeHtml(item.value)}</span>
      </div>
    `
        )
        .join("");
}

function renderProfileContacts(profile) {
    const container = document.querySelector('[data-bind="profileContacts"]');
    if (!container) return;

    container.innerHTML = `
    <div class="profile-contact-row">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      <span>${escapeHtml(profile.email)}</span>
    </div>
    <div class="profile-contact-row">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      <span>${escapeHtml(profile.location)}</span>
    </div>
    <div class="profile-status-badge">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      ${escapeHtml(profile.status)}
    </div>
  `;
}

function renderProfilePenalties(penalties) {
    const container = document.querySelector('[data-bind="profilePenalties"]');
    if (!container) return;

    container.innerHTML = penalties.length
        ? penalties.map((p) => `<p>${escapeHtml(p)}</p>`).join("")
        : '<p class="empty-text">You have no penalties</p>';
}

function renderProfileTribeContribution(contribution) {
    const container = document.querySelector('[data-bind="profileTribeContribution"]');
    if (!container) return;

    container.innerHTML = `
    <div class="tribe-contribution-card">
      <div class="tribe-contribution-icon">
        <svg viewBox="0 0 64 64" width="34" height="34">
          <rect x="12" y="8" width="40" height="32" rx="8" fill="#2a3142" stroke="#3d4559" stroke-width="2"/>
          <circle cx="22" cy="22" r="5" fill="#8bea9a"/>
          <circle cx="42" cy="22" r="5" fill="#8bea9a"/>
          <circle cx="22" cy="22" r="2" fill="#171d2b"/>
          <circle cx="42" cy="22" r="2" fill="#171d2b"/>
          <rect x="20" y="14" width="24" height="3" rx="1.5" fill="#434a5d"/>
          <rect x="28" y="32" width="8" height="4" rx="2" fill="#7dd3fc"/>
          <rect x="18" y="42" width="6" height="12" rx="3" fill="#2a3142" stroke="#3d4559" stroke-width="1.5"/>
          <rect x="40" y="42" width="6" height="12" rx="3" fill="#2a3142" stroke="#3d4559" stroke-width="1.5"/>
          <rect x="26" y="40" width="12" height="4" rx="2" fill="#434a5d"/>
        </svg>
      </div>
      <div class="tribe-contribution-info">
        <div class="tribe-contribution-name">
          ${escapeHtml(contribution.name)}
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
          ${contribution.points}
        </div>
        <p>${escapeHtml(contribution.text)}</p>
      </div>
    </div>
  `;
}

function renderProfileBadges(badges) {
    const container = document.querySelector('[data-bind="profileBadges"]');
    if (!container) return;

    container.innerHTML = badges
        .map(
            (badge) => `
      <div class="profile-badge-item">
        <div class="profile-badge-icon">${badge.icon}</div>
        <span class="profile-badge-title">${escapeHtml(badge.title)}</span>
      </div>
    `
        )
        .join("");
}

function getUserAvatarForProfile(name) {
    const svg = getUserAvatarSvg(name);
    const wrapper = document.createElement("div");
    wrapper.innerHTML = svg;
    return wrapper.firstElementChild;
}

function renderProfileXpGraph(graph) {
    const container = document.querySelector('[data-bind="profileXpGraph"]');
    if (!container) return;

    const width = 640;
    const height = 260;
    const padding = { top: 30, right: 30, bottom: 40, left: 60 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;

    const maxXp = Math.max(...graph.map((d) => d.xp), 2000);
    const points = graph.map((d, i) => {
        const x = padding.left + (i / (graph.length - 1)) * chartWidth;
        const y = padding.top + chartHeight - (d.xp / maxXp) * chartHeight;
        return { x, y, xp: d.xp, date: d.date };
    });

    const line = points.map((p, i) => `${i === 0 ? "M" : "L"} ${p.x} ${p.y}`).join(" ");

    container.innerHTML = `
    <svg viewBox="0 0 ${width} ${height}" width="100%" height="100%">
      <line x1="${padding.left}" y1="${padding.top + chartHeight}" x2="${padding.left + chartWidth}" y2="${padding.top + chartHeight}" stroke="#e5e7eb" stroke-width="1" />
      <line x1="${padding.left}" y1="${padding.top}" x2="${padding.left}" y2="${padding.top + chartHeight}" stroke="#e5e7eb" stroke-width="1" />
      <line x1="${padding.left}" y1="${padding.top + chartHeight / 2}" x2="${padding.left + chartWidth}" y2="${padding.top + chartHeight / 2}" stroke="#f3f4f6" stroke-width="1" />
      <text x="${padding.left - 10}" y="${padding.top + chartHeight + 4}" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">0</text>
      <text x="${padding.left - 10}" y="${padding.top + chartHeight / 2 + 4}" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">${Math.round(maxXp / 2)} XP</text>
      <path d="${line}" fill="none" stroke="#7dd3fc" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
      ${points
        .map(
            (p, i) => `
        <circle cx="${p.x}" cy="${p.y}" r="5" fill="#7dd3fc" stroke="#fff" stroke-width="2" />
        <text x="${p.x}" y="${padding.top + chartHeight + 18}" text-anchor="middle" font-size="11" fill="#9ca3af" font-weight="600">${escapeHtml(p.date)}</text>
      `
        )
        .join("")}
    </svg>
  `;
}

function renderProfileLogtime(logtime) {
    const container = document.querySelector('[data-bind="profileLogtime"]');
    if (!container) return;

    const width = 640;
    const height = 260;
    const padding = { top: 30, right: 30, bottom: 50, left: 50 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;

    const maxHours = Math.max(...logtime.days.map((d) => d.imac + d.campus), 10);
    const barWidth = (chartWidth / logtime.days.length) * 0.5;
    const gap = chartWidth / logtime.days.length;

    const bars = logtime.days
        .map((d, i) => {
            const x = padding.left + i * gap + gap / 2;
            const campusHeight = (d.campus / maxHours) * chartHeight;
            const imacHeight = (d.imac / maxHours) * chartHeight;
            const campusY = padding.top + chartHeight - campusHeight;
            const imacY = padding.top + chartHeight - imacHeight;
            return `
        <rect x="${x - barWidth / 2}" y="${campusY}" width="${barWidth}" height="${campusHeight}" rx="6" fill="#a7f3d0" />
        <rect x="${x - barWidth / 2}" y="${imacY}" width="${barWidth}" height="${imacHeight}" rx="6" fill="#8bea9a" />
        <text x="${x}" y="${padding.top + chartHeight + 18}" text-anchor="middle" font-size="11" fill="#9ca3af" font-weight="600">${escapeHtml(d.day)}</text>
      `;
        })
        .join("");

    container.innerHTML = `
    <svg viewBox="0 0 ${width} ${height}" width="100%" height="100%">
      <line x1="${padding.left}" y1="${padding.top + chartHeight}" x2="${padding.left + chartWidth}" y2="${padding.top + chartHeight}" stroke="#e5e7eb" stroke-width="1" />
      <line x1="${padding.left}" y1="${padding.top}" x2="${padding.left}" y2="${padding.top + chartHeight}" stroke="#e5e7eb" stroke-width="1" />
      <text x="${padding.left - 10}" y="${padding.top + chartHeight + 4}" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">0H</text>
      <text x="${padding.left - 10}" y="${padding.top + chartHeight / 2 + 4}" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">${Math.round(maxHours / 2)}H</text>
      ${bars}
    </svg>
  `;
}

function renderProfileLogtimeOniMac(data) {
    const container = document.querySelector('[data-bind="profileLogtimeOniMac"]');
    if (!container) return;

    container.innerHTML = `
    <div class="logtime-imac-ring">
      <svg viewBox="0 0 100 100" width="100" height="100">
        <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="8" />
        <circle cx="50" cy="50" r="42" fill="none" stroke="#7dd3fc" stroke-width="8" stroke-dasharray="220" stroke-dashoffset="55" stroke-linecap="round" transform="rotate(-90 50 50)" />
        <text x="50" y="56" text-anchor="middle" font-size="22" font-weight="800" fill="#111827">${data.average}</text>
      </svg>
    </div>
    <p class="logtime-imac-label">AVERAGE HOURS PER DAY</p>
    <div class="logtime-imac-week">${escapeHtml(data.week)}</div>
  `;
}

function initProfilePage() {
    const page = document.querySelector(".profile-page");
    if (!page) return;

    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get("user");
    const user = userId && usersData[userId] ? usersData[userId] : null;

    const profileData = user || data.profile;
    const userData = user
        ? { name: user.name, level: user.level, xp: user.xp, xpToNextLevel: user.xpToNextLevel, totalXp: user.totalXp, avatarUrl: user.avatarUrl || "" }
        : data.user;

    const tabs = document.querySelectorAll(".profile-tab");
    const panels = document.querySelectorAll(".profile-panel");

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            const target = tab.dataset.tab;
            tabs.forEach((t) => t.classList.toggle("active", t.dataset.tab === target));
            panels.forEach((p) => p.classList.toggle("active", p.dataset.panel === target));
        });
    });

    const progressPercent = Math.min(
        100,
        Math.round((userData.xp / userData.xpToNextLevel) * 100)
    );

    bindText("userName", userData.name);
    bindText("userLevel", `lvl ${userData.level}`);
    bindText("profileLevel", userData.level);
    bindText("userRole", profileData.role);
    bindText("userTotalXp", `${userData.totalXp} XP`);
    bindText("peerReviews", profileData.peerReviews);
    bindText("levelPercent", `${progressPercent}%`);

    setProfileProgress(progressPercent);

    const fallback = document.querySelector('[data-bind="avatarFallback"]');
    if (fallback && user) {
        fallback.innerHTML = getUserAvatarSvg(user.shortName);
        fallback.style.display = "flex";
        const img = document.querySelector('[data-bind="avatar"]');
        if (img) img.style.display = "none";
    } else {
        renderAvatar(userData.avatarUrl, userData.name);
    }

    renderSkillsRadar(profileData.skills);
    renderPeerFeedback(profileData.peerFeedback);
    renderProfileContacts(profileData);
    renderProfilePenalties(profileData.penalties || []);
    renderProfileTribeContribution(profileData.tribeContribution);
    renderProfileBadges(profileData.badges);
    renderProfileXpGraph(profileData.xpGraph);
    renderProfileLogtime(profileData.logtime);
    renderProfileLogtimeOniMac(profileData.logtimeOniMac);
}

/* ========================================
   Tournament list
   ======================================== */

function getUserAvatarSvg(name) {
    const initials = name
        .split(/[^a-zA-Z0-9]/)
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0].toUpperCase())
        .join("");
    const colors = ["#a855f7", "#7c3aed", "#ec4899", "#f43f5e", "#3b82f6", "#0ea5e9", "#10b981", "#f59e0b"];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    const color = colors[Math.abs(hash) % colors.length];
    return `<svg viewBox="0 0 64 64" width="40" height="40"><circle cx="32" cy="32" r="32" fill="${color}"/><text x="32" y="38" text-anchor="middle" font-size="22" font-weight="700" fill="#fff">${initials || "?"}</text></svg>`;
}

function renderTournamentList() {
    const container = document.querySelector('[data-bind="tournamentList"]');
    if (!container) return;

    container.innerHTML = data.tournaments
        .map((tournament) => {
            const topMembers = tournament.top.map((id) => usersData[id]).filter(Boolean);
            const lastMember = usersData[tournament.last];
            const visibleTop = topMembers.slice(0, 3);

            return `
        <article class="tournament-tribe-card${tournament.expanded ? " expanded" : ""}" data-tournament-id="${tournament.id}">
          <div class="tournament-tribe-accent" style="background: ${tournament.accent};"></div>
          <div class="tournament-tribe-summary">
            <div class="tournament-tribe-main">
              <div class="tournament-tribe-icon" style="background: ${tournament.accent}20;">${tournament.icon}</div>
              <div class="tournament-tribe-info">
                <div class="tournament-tribe-name">
                  ${escapeHtml(tournament.name)}
                  <span class="tournament-tribe-members">${tournament.membersCount} members</span>
                </div>
                <div class="tournament-tribe-points">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                  ${tournament.points.toLocaleString("en-US")} tribe points
                </div>
              </div>
            </div>
            <div class="tournament-tribe-preview">
              ${visibleTop
                .map(
                    (user) => `
                <div class="tournament-preview-member" title="${escapeHtml(user.name)} – ${user.tribePoints} tribe points">
                  <div class="tournament-preview-avatar">${getUserAvatarSvg(user.shortName)}</div>
                  <div class="tournament-preview-info">
                    <span class="tournament-preview-name">${escapeHtml(user.name)}</span>
                    <span class="tournament-preview-points">${user.tribePoints} tribe points</span>
                  </div>
                </div>
              `
                )
                .join("")}
            </div>
            <button class="tournament-tribe-toggle" aria-label="Expand tournament">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
          </div>
          <div class="tournament-tribe-details">
            <div class="tournament-leaderboard">
              ${topMembers
                .map(
                    (user, index) => `
                <a href="user-profile.html?user=${usersDataKeyByName(user.name)}" class="tournament-leaderboard-row${index === 0 ? " first-place" : ""}">
                  <div class="leaderboard-rank">${user.rank}<span>place</span></div>
                  <div class="leaderboard-user">
                    <div class="leaderboard-avatar">${getUserAvatarSvg(user.shortName)}</div>
                    <div class="leaderboard-info">
                      <span class="leaderboard-name">${escapeHtml(user.name)}</span>
                      <span class="leaderboard-level">level ${user.level}</span>
                    </div>
                  </div>
                  <div class="leaderboard-points">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    ${user.tribePoints} tribe points
                  </div>
                </a>
              `
                )
                .join("")}
            </div>
            ${lastMember
                ? `
            <div class="tournament-leaderboard-last">
              <a href="user-profile.html?user=${usersDataKeyByName(lastMember.name)}" class="tournament-leaderboard-row">
                <div class="leaderboard-rank">${lastMember.rank}<span>place</span></div>
                <div class="leaderboard-user">
                  <div class="leaderboard-avatar">${getUserAvatarSvg(lastMember.shortName)}</div>
                  <div class="leaderboard-info">
                    <span class="leaderboard-name">${escapeHtml(lastMember.name)}</span>
                    <span class="leaderboard-level">level ${lastMember.level}</span>
                  </div>
                </div>
                <div class="leaderboard-points">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                  ${lastMember.tribePoints} tribe point
                </div>
              </a>
            </div>
            `
                : ""}
          </div>
        </article>
      `;
        })
        .join("");

    container.querySelectorAll(".tournament-tribe-card").forEach((card) => {
        const summary = card.querySelector(".tournament-tribe-summary");
        if (!summary) return;
        summary.addEventListener("click", (e) => {
            if (e.target.closest("a") || e.target.closest("button")) return;
            card.classList.toggle("expanded");
        });
        const toggle = card.querySelector(".tournament-tribe-toggle");
        if (toggle) {
            toggle.addEventListener("click", () => card.classList.toggle("expanded"));
        }
    });
}

function usersDataKeyByName(name) {
    return Object.keys(usersData).find((key) => usersData[key].name === name) || "";
}

/* ========================================
   Initialize
   ======================================== */

function init() {
    const progressPercent = Math.min(
        100,
        Math.round((data.user.xp / data.user.xpToNextLevel) * 100)
    );

    animateLevelText(data.user.level, progressPercent);
    bindText("userName", data.user.name);
    bindText("userLevel", `lvl ${data.user.level}`);
    bindText("profileLevel", data.user.level);
    bindText("userRole", data.user.role);
    bindText("userTotalXp", `${data.user.totalXp} XP`);
    bindText("peerReviews", data.profile.peerReviews);
    bindText("levelPercent", `${progressPercent}%`);
    bindText("year", new Date().getFullYear());

    setProgress(progressPercent);
    setProfileProgress(progressPercent);
    renderAvatar(data.user.avatarUrl, data.user.name);
    renderStats(data.stats);
    renderEventsCard(data.events);
    renderAgendaCard(data.agenda);
    renderBadgesCard(data.badges);
    renderTournamentList();

    initActivities();
    initScrollReveal();
    initPremiumFX();
    initProjectsOverlay();
    initProjectsPage();
    initProjectDetailsPage();
    initTribesTabs();
    initNotifications();
    initUserMenu();
    initProfilePage();
    initProjectMap();
    renderUserProfileBadgesAll();
    initAuthPages();
    initGitlabPage();
    initProfileDropdowns();
}

/* Profile "Weekly/Monthly" dropdown: functional period toggle */
function initProfileDropdowns() {
    document.querySelectorAll(".profile-dropdown").forEach((btn) => {
        btn.addEventListener("click", () => {
            const isWeekly = btn.textContent.trim() === "Weekly";
            btn.textContent = isWeekly ? "Monthly" : "Weekly";

            // Update the visible period caption if the logtime widget is rendered
            const card = btn.closest(".profile-section-card");
            const week = card ? card.querySelector(".logtime-imac-week") : null;
            if (week) {
                week.textContent = isWeekly ? "March 2023" : "13 March – 19 March 2023";
            }
        });
    });
}

function renderUserProfileBadgesAll() {
    const container = document.querySelector('[data-bind="profileBadgesAll"]');
    if (!container) return;

    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get("user");
    const user = userId && usersData[userId] ? usersData[userId] : null;
    const badges = (user || data.profile).badges;

    container.innerHTML = badges
        .map(
            (badge) => `
      <div class="profile-badge-item">
        <div class="profile-badge-icon">${badge.icon}</div>
        <span class="profile-badge-title">${escapeHtml(badge.title)}</span>
      </div>
    `
        )
        .join("");
}

/* ========================================
   Projects overlay
   ======================================== */

/* ========================================
   Project map
   ======================================== */

const projectMapData = {
    linear: [
        { id: "T01D01", name: "T01D01", type: "individual", status: "completed", xp: 50, mandatory: true, days: 1, hours: 12, desc: "First steps into the School 21 program.", tags: ["Basics"] },
        { id: "T02D02", name: "T02D02", type: "individual", status: "completed", xp: 60, mandatory: true, days: 1, hours: 16, desc: "Introduction to the command line interface.", tags: ["CLI"] },
        { id: "T03D03", name: "T03D03", type: "individual", status: "completed", xp: 70, mandatory: true, days: 2, hours: 20, desc: "Working with Git and version control.", tags: ["Git"] },
        { id: "T04D04", name: "T04D04", type: "individual", status: "completed", xp: 80, mandatory: true, days: 1, hours: 18, desc: "Basic C programming concepts.", tags: ["C"] },
        { id: "E01D05", name: "E01D05", type: "exam", status: "completed", xp: 100, mandatory: true, days: 1, hours: 24, desc: "First examination of core basics.", tags: ["Exam"] },
        { id: "P01D06", name: "P01D06", type: "group", status: "completed", xp: 120, mandatory: true, days: 2, hours: 30, desc: "Collaborative project on simple utilities.", tags: ["C", "Team"] },
        { id: "T05D08", name: "T05D08", type: "individual", status: "completed", xp: 90, mandatory: true, days: 2, hours: 22, desc: "Advanced C functions and arrays.", tags: ["C"] },
        { id: "T06D09", name: "T06D09", type: "individual", status: "completed", xp: 100, mandatory: true, days: 2, hours: 26, desc: "Pointers and memory management in C.", tags: ["C", "Memory"] },
        { id: "T07D10", name: "T07D10", type: "individual", status: "completed", xp: 110, mandatory: true, days: 2, hours: 28, desc: "Strings and structures in C.", tags: ["C"] },
        { id: "T08D11", name: "T08D11", type: "individual", status: "completed", xp: 120, mandatory: true, days: 2, hours: 30, desc: "File input and output operations.", tags: ["C", "IO"] },
        { id: "E02D12", name: "E02D12", type: "exam", status: "completed", xp: 150, mandatory: true, days: 1, hours: 32, desc: "Mid-core examination.", tags: ["Exam"] },
        { id: "P02D13", name: "P02D13", type: "group", status: "completed", xp: 180, mandatory: true, days: 3, hours: 38, desc: "Building Bash utilities as a team.", tags: ["Bash", "Team"] },
        { id: "T09D15", name: "T09D15", type: "individual", status: "available", xp: 130, mandatory: true, days: 2, hours: 30, desc: "Math library and algorithms basics.", tags: ["Math", "Algorithms"] },
        { id: "T10D16", name: "T10D16", type: "individual", status: "available", xp: 140, mandatory: true, days: 2, hours: 32, desc: "Advanced algorithms and complexity.", tags: ["Algorithms"] },
        { id: "T11D17", name: "T11D17", type: "individual", status: "available", xp: 150, mandatory: true, days: 2, hours: 34, desc: "Data structures fundamentals.", tags: ["Data Structures"] },
        { id: "T12D18", name: "T12D18", type: "individual", status: "available", xp: 160, mandatory: true, days: 2, hours: 36, desc: "Linked lists and abstract data types.", tags: ["C", "Data Structures"] },
        { id: "E03D19", name: "E03D19", type: "exam", status: "available", xp: 200, mandatory: true, days: 1, hours: 40, desc: "Core program final examination.", tags: ["Exam"] },
        { id: "P03D20", name: "P03D20", type: "group", status: "available", xp: 220, mandatory: true, days: 4, hours: 42, desc: "Large group project on system utilities.", tags: ["C", "Team"] },
        { id: "T13D22", name: "T13D22", type: "individual", status: "available", xp: 150, mandatory: true, days: 1, hours: 38, desc: "This day will help you get acquainted with text files processing.", tags: ["Algorithms", "Structured programming", "Linux", "C"] },
        { id: "T14D23", name: "T14D23", type: "individual", status: "locked", xp: 170, mandatory: true, days: 2, hours: 40, desc: "Network programming basics.", tags: ["Network"] },
        { id: "T15D24", name: "T15D24", type: "individual", status: "locked", xp: 180, mandatory: true, days: 2, hours: 42, desc: "Final individual project of the core.", tags: ["C"] },
    ],
};

let projectMapState = {
    open: false,
    view: "branching",
    zoom: 1,
    pan: { x: 0, y: 0 },
    isDragging: false,
    lastMouse: { x: 0, y: 0 },
};

function openProjectMap() {
    document.body.classList.add("project-map-open");
    projectMapState.open = true;
    renderProjectMap();
}

function closeProjectMap() {
    document.body.classList.remove("project-map-open");
    projectMapState.open = false;
}

function initProjectMap() {
    const toggles = document.querySelectorAll(".project-map-toggle");
    const overlay = document.getElementById("projectMapOverlay");
    const pageContainer = document.getElementById("projectMapContainer");
    const isInline = !!pageContainer;
    if (!toggles.length && !isInline && !overlay) return;

    if (!isInline && overlay) {
        toggles.forEach((toggle) => {
            toggle.addEventListener("click", (e) => {
                e.preventDefault();
                openProjectMap();
            });
        });

        const closeBtn = overlay ? overlay.querySelector(".project-map-close") : null;
        if (closeBtn) closeBtn.addEventListener("click", closeProjectMap);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && document.body.classList.contains("project-map-open")) {
                closeProjectMap();
            }
        });
    }

    if (isInline) {
        renderProjectMap();
    }

    const viewToggle = document.getElementById("mapViewToggle");
    if (viewToggle) {
        viewToggle.addEventListener("click", () => {
            projectMapState.view = projectMapState.view === "linear" ? "branching" : "linear";
            projectMapState.zoom = 1;
            projectMapState.pan = { x: 0, y: 0 };
            updateMapTransform();
            renderProjectMap();
        });
    }

    const zoomIn = document.getElementById("mapZoomIn");
    const zoomOut = document.getElementById("mapZoomOut");
    const fit = document.getElementById("mapFit");
    const startBtn = document.getElementById("mapStartButton");

    if (zoomIn) zoomIn.addEventListener("click", () => changeZoom(0.15));
    if (zoomOut) zoomOut.addEventListener("click", () => changeZoom(-0.15));
    if (fit) fit.addEventListener("click", fitMap);
    if (startBtn) {
        startBtn.addEventListener("click", () => {
            coreCompleteActive();
        });
    }

    const container = document.getElementById("projectMapContainer");
    if (container) {
        container.addEventListener("mousedown", (e) => {
            if (e.target.closest(".linear-node") || e.target.closest(".core-hex")) return;
            hideProjectTooltip();
            projectMapState.isDragging = true;
            projectMapState.lastMouse = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener("mousemove", (e) => {
            if (!projectMapState.isDragging) return;
            const dx = e.clientX - projectMapState.lastMouse.x;
            const dy = e.clientY - projectMapState.lastMouse.y;
            projectMapState.pan.x += dx;
            projectMapState.pan.y += dy;
            projectMapState.lastMouse = { x: e.clientX, y: e.clientY };
            updateMapTransform();
        });

        window.addEventListener("mouseup", () => {
            projectMapState.isDragging = false;
        });

        container.addEventListener("wheel", (e) => {
            e.preventDefault();
            changeZoom(e.deltaY > 0 ? -0.1 : 0.1);
        }, { passive: false });
    }

    document.addEventListener("click", (e) => {
        if (!e.target.closest(".linear-node") && !e.target.closest(".core-hex") && !e.target.closest(".project-map-tooltip")) {
            hideProjectTooltip();
        }
    });

    const search = document.getElementById("projectMapSearch");
    if (search) {
        search.addEventListener("input", (e) => {
            const query = e.target.value.toLowerCase();
            highlightMapNodes(query);
        });

        // Deep-link support: header/hero "Search" links point to project-map.html#search
        if (window.location.hash === "#search") {
            search.focus();
        }
    }

    // Filter button next to the search field: clears the query and resets highlighting
    const filterBtn = document.querySelector(".project-map-filter");
    if (filterBtn && search) {
        filterBtn.addEventListener("click", () => {
            search.value = "";
            highlightMapNodes("");
            search.focus();
        });
    }
}

function changeZoom(delta) {
    projectMapState.zoom = Math.max(0.4, Math.min(2.5, projectMapState.zoom + delta));
    updateMapTransform();
}

function fitMap() {
    projectMapState.zoom = 1;
    projectMapState.pan = { x: 0, y: 0 };
    updateMapTransform();
}

function updateMapTransform() {
    const stage = document.getElementById("projectMapStage");
    if (!stage) return;
    stage.style.transform = `translate(${projectMapState.pan.x}px, ${projectMapState.pan.y}px) scale(${projectMapState.zoom})`;
}

function renderProjectMap() {
    const linear = document.getElementById("mapLinear");
    const branching = document.getElementById("mapBranching");
    const startBtn = document.getElementById("mapStartButton");
    if (!linear || !branching) return;

    if (projectMapState.view === "linear") {
        linear.classList.add("active");
        branching.classList.remove("active");
        if (startBtn) startBtn.style.display = "none";
        renderLinearMap();
    } else {
        linear.classList.remove("active");
        branching.classList.add("active");
        if (startBtn) startBtn.style.display = "block";
        renderCoreMap();
    }
}

function renderLinearMap() {
    const container = document.getElementById("linearNodes");
    const track = document.querySelector(".linear-track");
    if (!container) return;

    const width = container.offsetWidth || 1200;
    const height = container.offsetHeight || 700;
    const startX = width * 0.12;
    const startY = height * 0.85;
    const endX = width * 0.82;
    const endY = height * 0.12;
    const total = projectMapData.linear.length - 1;

    const dx = endX - startX;
    const dy = endY - startY;
    const length = Math.sqrt(dx * dx + dy * dy);
    const angle = (Math.atan2(dy, dx) * 180) / Math.PI;

    if (track) {
        track.style.width = `${length}px`;
        track.style.left = `${startX}px`;
        track.style.bottom = `${height - startY}px`;
        track.style.transform = `rotate(${angle}deg)`;
        track.style.transformOrigin = "left center";
    }

    container.innerHTML = projectMapData.linear
        .map((project, index) => {
            const t = index / total;
            const x = startX + dx * t;
            const y = startY + dy * t;
            const left = `${x}px`;
            const top = `${y}px`;

            const shapeClass = project.type === "group" ? "group" : project.type === "exam" ? "exam" : "";
            const optionalClass = project.mandatory ? "" : "optional";
            const icon = project.type === "group"
                ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="6" r="3"/><circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/></svg>`
                : project.type === "exam"
                    ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4L4 20h16L12 4z"/></svg>`
                    : `<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="5"/></svg>`;

            return `
        <div class="linear-node ${project.status} ${optionalClass}" data-project-id="${escapeHtml(project.id)}" style="left:${left};top:${top}">
          <div class="node-shape ${shapeClass}">${icon}</div>
          <span class="node-label">${escapeHtml(project.name)}</span>
        </div>
      `;
        })
        .join("");

    container.querySelectorAll(".linear-node").forEach((node) => {
        node.addEventListener("click", (e) => {
            const id = node.dataset.projectId;
            const project = projectMapData.linear.find((p) => p.id === id);
            if (project) showProjectTooltip(e, project, node);
        });
    });
}

/* ========================================
   Common Core branching map
   ======================================== */

const coreMapData = {
    // Pointy-top hexes, radius 52.
    nodes: [
        // ----- C cluster (bottom center) -----
        { id: "Pool", label: "Pool (C1)", x: 800, y: 1030, type: "pool",    status: "done",    labelPos: "left",
            xp: 300, hours: 120, days: 26, desc: "Survival camp: the entry pool of the Common Core.", tags: ["C", "Basics"] },
        { id: "C2",   label: "C2",       x: 800, y: 926,  type: "project", status: "active",  labelPos: "right",
            xp: 150, hours: 38,  days: 8,  desc: "SimpleBashUtils: text processing in the shell.",   tags: ["Bash", "C"] },
        { id: "C3",   label: "C3",       x: 755, y: 848,  type: "team",    status: "pending", labelPos: "left",
            xp: 200, hours: 40,  days: 9,  desc: "s21_string+: team implementation of string.h.",    tags: ["C", "Team"] },
        { id: "C4",   label: "C4",       x: 845, y: 848,  type: "team",    status: "pending", labelPos: "right",
            xp: 250, hours: 48,  days: 10, desc: "s21_math: your own math library.",                 tags: ["C", "Team"] },
        { id: "C5",   label: "C5",       x: 710, y: 770,  type: "team",    status: "pending", labelPos: "up",
            xp: 300, hours: 52,  days: 11, desc: "s21_decimal: big decimal arithmetic.",             tags: ["C", "Team"] },
        { id: "C7",   label: "C7",       x: 800, y: 770,  type: "project", status: "pending", labelPos: "up",
            xp: 200, hours: 36,  days: 8,  desc: "SmartCalc: calculator with a GUI.",                tags: ["C", "GUI"] },
        { id: "C6",   label: "C6",       x: 890, y: 770,  type: "project", status: "pending", labelPos: "up",
            xp: 250, hours: 44,  days: 9,  desc: "s21_matrix: matrix computation library.",          tags: ["C", "Math"] },
        { id: "C8",   label: "C8",       x: 800, y: 666,  type: "team",    status: "pending", labelPos: "up",
            xp: 350, hours: 60,  days: 13, desc: "3DViewer: wireframe model viewer.",                tags: ["C", "Team", "GUI"] },

        // ----- DO cluster (top left) -----
        { id: "DO1", label: "DO1", x: 605, y: 570, type: "project", status: "pending", labelPos: "left",
            xp: 150, hours: 24, days: 5, desc: "Linux: installation and configuration.",             tags: ["Linux"] },
        { id: "DO2", label: "DO2", x: 560, y: 492, type: "project", status: "pending", labelPos: "left",
            xp: 200, hours: 30, days: 6, desc: "Linux Network: networks and routing.",               tags: ["Linux", "Network"] },
        { id: "DO3", label: "DO3", x: 650, y: 492, type: "project", status: "pending", labelPos: "right",
            xp: 200, hours: 30, days: 6, desc: "Linux Monitoring v1.0: system status research.",     tags: ["Linux", "DevOps"] },
        { id: "DO4", label: "DO4", x: 515, y: 414, type: "exam",    status: "pending", labelPos: "left",
            xp: 350, hours: 32, days: 4, desc: "LinuxMonitoring v2.0: real-time monitoring exam.",   tags: ["Linux", "DevOps"] },
        { id: "DO5", label: "DO5", x: 605, y: 414, type: "project", status: "pending", labelPos: "right",
            xp: 250, hours: 36, days: 7, desc: "SimpleDocker: introduction to containers.",          tags: ["Docker", "DevOps"] },

        // ----- CPP cluster (top right) -----
        { id: "CPP1", label: "CPP1", x: 1105, y: 490, type: "exam",    status: "pending", labelPos: "right",
            xp: 300, hours: 42, days: 8, desc: "matrix+: object-oriented matrix library.",           tags: ["C++", "OOP"] },
        { id: "CPP3", label: "CPP3", x: 1060, y: 412, type: "project", status: "pending", labelPos: "left",
            xp: 300, hours: 46, days: 9, desc: "SmartCalc v2.0: calculator rewritten in C++.",       tags: ["C++", "MVC"] },
        { id: "CPP2", label: "CPP2", x: 1150, y: 412, type: "team",    status: "pending", labelPos: "right",
            xp: 350, hours: 50, days: 10, desc: "s21_containers: STL-like container library.",       tags: ["C++", "STL", "Team"] },
    ],

    // Completion order — project by project, following the layout:
    // up the C cluster, then the left DO branch, then the right CPP branch.
    order: ["C2", "C3", "C4", "C5", "C7", "C6", "C8",
        "DO1", "DO2", "DO3", "DO4", "DO5",
        "CPP1", "CPP3", "CPP2"],
    progress: 0, // index in `order` of the project that is currently active

    // Thick dark pipes drawn behind the hexes.
    trunks: [
        { o: "v", x: 800,  y1: 666, y2: 490 },
        { o: "h", y: 570,  x1: 605, x2: 800 },
        { o: "h", y: 490,  x1: 800, x2: 1105 },
    ],
};

const CORE_HEX_R = 52;

function coreHexIcon(type) {
    switch (type) {
        case "pool":
            return '<rect x="-10" y="-10" width="20" height="20" rx="4" fill="none" stroke="currentColor" stroke-width="3.6"/>';
        case "team":
            return '<circle cx="-4" cy="-7" r="2.3" fill="none" stroke="currentColor" stroke-width="1.7"/>' +
                '<circle cx="4"  cy="-5" r="2.3" fill="none" stroke="currentColor" stroke-width="1.7"/>' +
                '<circle cx="-7" cy="1"  r="2.3" fill="none" stroke="currentColor" stroke-width="1.7"/>' +
                '<circle cx="1"  cy="3"  r="2.3" fill="none" stroke="currentColor" stroke-width="1.7"/>' +
                '<circle cx="7"  cy="6"  r="2.3" fill="none" stroke="currentColor" stroke-width="1.7"/>';
        case "exam":
            return '<circle cx="0" cy="0" r="9" fill="none" stroke="currentColor" stroke-width="6"/>';
        case "project":
        default:
            return '<path d="M 9.6 -3.6 A 10.3 10.3 0 1 0 10.3 -0.4" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round"/>';
    }
}

// Hex corner coordinates, starting from the TOP vertex, clockwise.
function coreHexCorners(r) {
    const pts = [];
    for (let i = 0; i < 6; i++) {
        const a = (Math.PI / 3) * i - Math.PI / 2;
        pts.push([r * Math.cos(a), r * Math.sin(a)]);
    }
    return pts;
}

function coreHexPointsAttr(r) {
    return coreHexCorners(r).map((p) => p[0].toFixed(2) + "," + p[1].toFixed(2)).join(" ");
}

// Closed path that starts at the top corner — the pen starts here
// and travels corner by corner around the pencil outline.
function coreHexPathD(r) {
    const pts = coreHexCorners(r);
    return pts.map((p, i) => (i === 0 ? "M" : "L") + " " + p[0].toFixed(2) + " " + p[1].toFixed(2)).join(" ") + " Z";
}

function renderCoreMap() {
    const svg = document.getElementById("coreMapSvg");
    if (!svg) return;

    const trunksGroup = document.getElementById("coreTrunks");
    const nodesGroup = document.getElementById("coreNodes");
    if (!trunksGroup || !nodesGroup) return;

    trunksGroup.innerHTML = coreMapData.trunks.map((t) => {
        const x1 = t.o === "v" ? t.x : t.x1;
        const x2 = t.o === "v" ? t.x : t.x2;
        const y1 = t.o === "v" ? t.y1 : t.y;
        const y2 = t.o === "v" ? t.y2 : t.y;
        return `<line class="core-trunk" x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" />`;
    }).join("");

    nodesGroup.innerHTML = coreMapData.nodes.map((n) => {
        const labelGap = 18;
        let lx = 0, ly = 6, anchor = "middle";
        if (n.labelPos === "left")  { lx = -CORE_HEX_R - labelGap; anchor = "end"; }
        if (n.labelPos === "right") { lx =  CORE_HEX_R + labelGap; anchor = "start"; }
        if (n.labelPos === "up")    { ly = -CORE_HEX_R - 14; }
        if (n.labelPos === "down")  { ly =  CORE_HEX_R + 26; }

        return `
      <g class="core-hex is-${n.status} type-${n.type}" data-core-id="${n.id}" transform="translate(${n.x}, ${n.y})">
        <g class="core-hex-inner">
          <polygon class="core-hex-shape" points="${coreHexPointsAttr(CORE_HEX_R)}" />
          <polygon class="core-hex-paint" points="${coreHexPointsAttr(CORE_HEX_R)}" />
          <path class="core-hex-ink core-hex-ink-thin"  d="${coreHexPathD(CORE_HEX_R)}" />
          <path class="core-hex-ink core-hex-ink-thick" d="${coreHexPathD(CORE_HEX_R)}" />
          <g class="core-hex-icon">${coreHexIcon(n.type)}</g>
          <text class="core-hex-label" x="${lx}" y="${ly}" text-anchor="${anchor}">${n.label}</text>
        </g>
      </g>`;
    }).join("");

    const xs = coreMapData.nodes.map((n) => n.x);
    const ys = coreMapData.nodes.map((n) => n.y);
    const pad = 190;
    const minX = Math.min(...xs) - pad;
    const minY = Math.min(...ys) - pad;
    const w = Math.max(...xs) - Math.min(...xs) + pad * 2;
    const h = Math.max(...ys) - Math.min(...ys) + pad * 2;
    svg.setAttribute("viewBox", `${minX} ${minY} ${w} ${h}`);

    svg.querySelectorAll(".core-hex").forEach((g) => {
        g.addEventListener("click", () => {
            const node = coreMapData.nodes.find((n) => n.id === g.dataset.coreId);
            if (!node) return;
            const rect = g.getBoundingClientRect();
            showProjectTooltip(
                { clientX: rect.left + rect.width / 2, clientY: rect.top },
                {
                    id: node.id,
                    name: node.label,
                    type: node.type === "team" ? "group" : node.type,
                    status: node.status === "done" ? "completed" : node.status === "active" ? "available" : "locked",
                    xp: node.xp,
                    mandatory: true,
                    days: node.days,
                    hours: node.hours,
                    desc: node.desc,
                    tags: node.tags,
                },
                g
            );
        });
    });
}

/*
  Completion animation ("pen over pencil"):
  1) a thin colored line runs corner-to-corner over the pencil outline;
  2) a thick pen line traces the same path on top of it;
  3) the hex is flooded with the same green paint.
*/
function coreCompleteActive() {
    const order = coreMapData.order;
    if (coreMapData.progress >= order.length) return;

    const id = order[coreMapData.progress];
    const node = coreMapData.nodes.find((n) => n.id === id);
    const g = document.querySelector(`.core-hex[data-core-id="${id}"]`);
    if (!node || !g || g.dataset.animating) return;
    g.dataset.animating = "1";

    const thin = g.querySelector(".core-hex-ink-thin");
    const thick = g.querySelector(".core-hex-ink-thick");
    const paint = g.querySelector(".core-hex-paint");

    const runInk = (el, duration, delay) => {
        const len = el.getTotalLength();
        el.style.strokeDasharray = len;
        el.style.strokeDashoffset = len;
        el.style.opacity = "1";
        el.getBoundingClientRect(); // force reflow
        el.style.transition = `stroke-dashoffset ${duration}ms linear ${delay}ms`;
        el.style.strokeDashoffset = "0";
    };

    // 1) thin colored line over the pencil
    runInk(thin, 550, 0);
    // 2) thick pen line, corner by corner, slightly behind
    runInk(thick, 700, 420);

    // 3) paint flood after the border is fully inked (~1120ms)
    setTimeout(() => {
        if (paint) paint.classList.add("painting");
    }, 1150);

    // 4) finalize: mark done, promote the next project to active
    setTimeout(() => {
        node.status = "done";
        g.classList.remove("is-active", "is-pending");
        g.classList.add("is-done");
        delete g.dataset.animating;

        coreMapData.progress += 1;
        const nextId = order[coreMapData.progress];
        if (nextId) {
            const nextNode = coreMapData.nodes.find((n) => n.id === nextId);
            const nextG = document.querySelector(`.core-hex[data-core-id="${nextId}"]`);
            if (nextNode) nextNode.status = "active";
            if (nextG) {
                nextG.classList.remove("is-pending");
                nextG.classList.add("is-active");
            }
        }
    }, 1800);
}

function showProjectTooltip(e, project, element) {
    const tooltip = document.getElementById("projectMapTooltip");
    if (!tooltip) return;

    const typeLabel = {
        individual: "Individual",
        group: "Group",
        exam: "Exam",
        pool: "Project",
        subpool: "Project",
        milestone: "Milestone",
    }[project.type] || "Project";
    document.getElementById("tooltipType").textContent = typeLabel;
    document.getElementById("tooltipStatus").textContent = project.status === "completed" ? "Completed" : project.status === "available" ? "Available" : "Locked";
    document.getElementById("tooltipStatus").className = "map-tooltip-status " + project.status;
    document.getElementById("tooltipTitle").innerHTML = `${escapeHtml(project.name)} <span>${project.xp} XP</span>`;
    document.getElementById("tooltipDesc").textContent = project.desc;
    document.getElementById("tooltipDays").textContent = `${project.days} day${project.days > 1 ? "s" : ""}`;
    document.getElementById("tooltipHours").textContent = `${project.hours} hours`;

    const tagsEl = document.getElementById("tooltipTags");
    const visibleTags = project.tags.slice(0, 3);
    const more = project.tags.length - visibleTags.length;
    tagsEl.innerHTML =
        visibleTags.map((tag) => `<span class="map-tooltip-tag">${escapeHtml(tag)}</span>`).join("") +
        (more > 0 ? `<span class="map-tooltip-tag more">+${more}</span>` : "");

    tooltip.classList.add("visible");

    const rect = tooltip.getBoundingClientRect();
    let left = e.clientX - rect.width / 2;
    let top = e.clientY - rect.height - 16;

    if (left < 16) left = 16;
    if (left + rect.width > window.innerWidth - 16) left = window.innerWidth - rect.width - 16;
    if (top < 16) top = e.clientY + 16;

    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;
}

function hideProjectTooltip() {
    const tooltip = document.getElementById("projectMapTooltip");
    if (tooltip) tooltip.classList.remove("visible");
}

function highlightMapNodes(query) {
    document.querySelectorAll(".linear-node, .core-hex").forEach((node) => {
        const match = node.textContent.toLowerCase().includes(query);
        node.style.opacity = query && !match ? "0.3" : "1";
    });
}

function completeProjectAnimation(element, type) {
    if (type === "linear") {
        const burst = document.createElement("div");
        burst.className = "node-completion";
        const shape = element.querySelector(".node-shape");
        const rect = shape.getBoundingClientRect();
        burst.style.left = `${rect.width / 2}px`;
        burst.style.top = `${rect.height / 2}px`;
        burst.style.width = "28px";
        burst.style.height = "28px";
        burst.style.borderRadius = "50%";
        burst.style.background = "var(--green)";
        element.appendChild(burst);
        setTimeout(() => burst.remove(), 800);
    } else {
        element.classList.add("hex-completion");
        setTimeout(() => element.classList.remove("hex-completion"), 800);
    }
}

/* ========================================
   Premium scroll-reveal animations
   Elements gently fade + rise as they enter the viewport.
   Layout and sizes are untouched — opacity/transform only.
   ======================================== */
function initScrollReveal() {
    if (!("IntersectionObserver" in window)) return;
    if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    const selectors = [
        ".card",
        ".stats-card",
        ".badge-card",
        ".badges-header",
        ".prj-card",
        ".prj-column",
        ".prj-empty",
        ".pd-card",
        ".pd-sidebar",
        ".pd-repo-card",
        ".agenda-item",
        ".tournament-banner",
        ".tribes-tabs",
    ];

    const nodes = document.querySelectorAll(selectors.join(", "));
    if (!nodes.length) return;

    // Stagger siblings: each consecutive item in the same parent gets a small delay
    const parentCounters = new Map();
    nodes.forEach((el) => {
        // Skip elements inside overlays/modals — they have their own animations
        if (el.closest(".activities-overlay, .notifications-overlay, .user-menu-overlay, .pd-modal-overlay")) return;
        const parent = el.parentElement;
        const idx = parentCounters.get(parent) || 0;
        parentCounters.set(parent, idx + 1);
        el.style.setProperty("--reveal-delay", `${Math.min(idx * 70, 350)}ms`);
        el.classList.add("reveal-on-scroll");
    });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("revealed");
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.08, rootMargin: "0px 0px -30px 0px" }
    );

    document.querySelectorAll(".reveal-on-scroll").forEach((el) => observer.observe(el));
}

/* ========================================
   Premium FX: ripple, 3D tilt, counters, parallax
   ======================================== */
function initPremiumFX() {
    const reduced = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduced) return;

    initRippleFX();
    init3DTiltFX();
    initCounterFX();
    initHeroParallaxFX();
}

/* ---- Ripple on every interactive control ---- */
function initRippleFX() {
    const RIPPLE_SELECTOR = [
        ".pd-subscribe", ".pd-finish-btn", ".pd-modal-ok", ".pd-modal-cancel",
        ".prj-invite-accept", ".prj-invite-decline",
        ".prj-tab", ".pd-tab", ".calendar-tab", ".profile-tab", ".tribes-tab",
        ".notifications-filter", ".activity-btn", ".user-menu-item",
        ".map-start-button", ".map-tool-btn",
        ".search-btn", ".icon-btn", ".login-btn",
    ].join(", ");

    document.addEventListener("click", (e) => {
        const host = e.target.closest(RIPPLE_SELECTOR);
        if (!host) return;

        const rect = host.getBoundingClientRect();
        if (!rect.width || !rect.height) return;

        host.classList.add("fx-ripple-host");
        // Never override absolute/fixed positioning (e.g. the map start button)
        if (getComputedStyle(host).position === "static") {
            host.style.position = "relative";
        }

        const size = Math.max(rect.width, rect.height) * 1.15;
        const x = (e.clientX || rect.left + rect.width / 2) - rect.left - size / 2;
        const y = (e.clientY || rect.top + rect.height / 2) - rect.top - size / 2;

        const ripple = document.createElement("span");
        ripple.className = "fx-ripple";
        ripple.style.width = ripple.style.height = size + "px";
        ripple.style.left = x + "px";
        ripple.style.top = y + "px";
        host.appendChild(ripple);
        setTimeout(() => ripple.remove(), 700);
    });
}

/* ---- Subtle 3D tilt + moving shine on light cards ---- */
function init3DTiltFX() {
    const cards = document.querySelectorAll(".card, .prj-card, .badge-card, .pd-card");
    const MAX_TILT = 4; // degrees — subtle, premium

    cards.forEach((card) => {
        // Skip cards inside overlays
        if (card.closest(".activities-overlay, .notifications-overlay, .user-menu-overlay")) return;

        card.classList.add("fx-tilt");
        const shine = document.createElement("span");
        shine.className = "fx-tilt-shine";
        card.appendChild(shine);

        let raf = null;

        card.addEventListener("mousemove", (e) => {
            if (raf) return;
            raf = requestAnimationFrame(() => {
                raf = null;
                const rect = card.getBoundingClientRect();
                const px = (e.clientX - rect.left) / rect.width;
                const py = (e.clientY - rect.top) / rect.height;
                const rx = (0.5 - py) * MAX_TILT;
                const ry = (px - 0.5) * MAX_TILT;
                card.style.transform = `perspective(900px) rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg) translateY(-4px)`;
                card.style.setProperty("--shine-x", (px * 100).toFixed(1) + "%");
                card.style.setProperty("--shine-y", (py * 100).toFixed(1) + "%");
            });
        });

        card.addEventListener("mouseleave", () => {
            if (raf) { cancelAnimationFrame(raf); raf = null; }
            card.style.transform = "";
        });
    });
}

/* ---- Animated number counters (stats, XP, counts) ---- */
function initCounterFX() {
    if (!("IntersectionObserver" in window)) return;

    const candidates = document.querySelectorAll(".stat-item h3, .prj-count, .pd-fact-value");
    const targets = [];

    candidates.forEach((el) => {
        const text = el.textContent.trim();
        const match = text.match(/^(\d[\d\s,\.]*)/);
        if (!match) return;
        const num = parseInt(match[1].replace(/[\s,\.]/g, ""), 10);
        if (isNaN(num) || num === 0 || num > 100000) return;
        targets.push({ el, num, text });
    });

    if (!targets.length) return;

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const t = targets.find((x) => x.el === entry.target);
            io.unobserve(entry.target);
            if (!t) return;

            t.el.classList.add("fx-counter");
            const suffix = t.text.slice(String(t.num).length ? t.text.match(/^(\d[\d\s,\.]*)/)[1].length : 0);
            const dur = Math.min(900 + t.num * 8, 1600);
            const start = performance.now();

            function tick(now) {
                const p = Math.min((now - start) / dur, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                t.el.textContent = Math.round(t.num * eased) + suffix;
                if (p < 1) requestAnimationFrame(tick);
                else t.el.textContent = t.text;
            }
            requestAnimationFrame(tick);
        });
    }, { threshold: 0.4 });

    targets.forEach((t) => io.observe(t.el));
}

/* ---- Gentle parallax of hero background on mouse ---- */
function initHeroParallaxFX() {
    const heroBg = document.querySelector(".hero .hero-bg");
    if (!heroBg) return;

    const hero = document.querySelector(".hero");
    let raf = null;

    hero.addEventListener("mousemove", (e) => {
        if (raf) return;
        raf = requestAnimationFrame(() => {
            raf = null;
            const rect = hero.getBoundingClientRect();
            const dx = ((e.clientX - rect.left) / rect.width - 0.5) * 14;
            const dy = ((e.clientY - rect.top) / rect.height - 0.5) * 10;
            heroBg.style.transform = `translate(${dx.toFixed(1)}px, ${dy.toFixed(1)}px) scale(1.04)`;
            heroBg.style.transition = "transform 0.35s cubic-bezier(0.22, 1, 0.36, 1)";
        });
    });

    hero.addEventListener("mouseleave", () => {
        heroBg.style.transform = "";
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
} else {
    init();
}


/* ========================================
   Auth pages (login.html / register.html)
   moved from inline scripts
   ======================================== */
function initAuthPages() {
    if (!document.body.classList.contains("auth-page")) return;

    /* ---------- Slogan carousel ---------- */
    var slides = document.querySelectorAll(".slide");
    var dots = document.querySelectorAll(".dot");
    if (slides.length && dots.length) {
        var current = 0;
        var timer = null;

        var goTo = function (i) {
            current = i % slides.length;
            slides.forEach(function (s, idx) { s.classList.toggle("active", idx === current); });
            dots.forEach(function (d, idx) { d.classList.toggle("active", idx === current); });
        };

        var autoplay = function () {
            clearInterval(timer);
            timer = setInterval(function () { goTo(current + 1); }, 5000);
        };

        dots.forEach(function (d) {
            d.addEventListener("click", function () {
                goTo(Number(d.dataset.slide));
                autoplay();
            });
        });
        autoplay();
    }

    /* ---------- Show / hide password ---------- */
    document.querySelectorAll(".eye-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var input = document.getElementById(btn.dataset.eye);
            if (!input) return;
            var revealed = input.type === "password";
            input.type = revealed ? "text" : "password";
            btn.classList.toggle("is-revealed", revealed);
            btn.setAttribute("aria-label", revealed ? "Hide password" : "Show password");
        });
    });

    /* ---------- Toast ---------- */
    function showAuthToast(msg) {
        var toast = document.getElementById("authToast");
        if (!toast) return;
        var text = document.getElementById("authToastText");
        if (text) text.textContent = msg;
        toast.classList.add("show");
        setTimeout(function () { toast.classList.remove("show"); }, 4000);
    }

    /* ---------- Login: AJAX submit with error display ---------- */
    var loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();
            var email = document.getElementById("loginEmail").value.trim();
            var pass = document.getElementById("loginPassword").value;
            var err = document.getElementById("loginError");

            if (!email || !pass) {
                err.textContent = "Please enter both email and password.";
                err.classList.add("show");
                return;
            }
            err.classList.remove("show");
            err.textContent = "";

            // AJAX login to API endpoint (no CSRF needed for API)
            fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: pass,
                }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (res.ok && data.redirect) {
                            // Success - redirect
                            window.location.href = data.redirect;
                        } else {
                            // Error - show message
                            err.textContent = data.error || 'Invalid email or password.';
                            err.classList.add('show');
                        }
                    });
                })
                .catch(function () {
                    err.textContent = 'Connection error. Please try again.';
                    err.classList.add('show');
                });
        });
    }

    /* ---------- Register: AJAX submit with error display ---------- */
    var registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", function (e) {
            e.preventDefault();
            var name = document.getElementById("regName").value.trim();
            var email = document.getElementById("regEmail").value.trim();
            var p1 = document.getElementById("regPassword").value;
            var p2 = document.getElementById("regPassword2").value;
            var err = document.getElementById("registerError");

            var msg = "";
            if (!name || !email || !p1 || !p2) {
                msg = "Please fill in all fields.";
            } else if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
                msg = "Please enter a valid email address.";
            } else if (p1.length < 8) {
                msg = "Password must be at least 8 characters long.";
            } else if (p1 !== p2) {
                msg = "Passwords do not match.";
            }

            if (msg) {
                err.textContent = msg;
                err.classList.add("show");
                return;
            }
            err.classList.remove("show");
            err.textContent = "";

            // AJAX register
            fetch('/api/auth/register', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: p1,
                    password_confirmation: p2,
                }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (res.ok && data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            err.textContent = data.error || data.errors?.email?.[0] || 'Registration failed.';
                            err.classList.add('show');
                        }
                    });
                })
                .catch(function () {
                    err.textContent = 'Connection error. Please try again.';
                    err.classList.add('show');
                });
        });
    }
}

/* ========================================
   GitLab sign-in page (gitlab.html)
   moved from inline script
   ======================================== */
function initGitlabPage() {
    if (!document.body.classList.contains("gitlab-page")) return;

    /* GitLab sign-in: client-side validation only; the form
       has a real action/method and submits natively when valid. */
    var signInForm = document.getElementById("glSignInForm");
    if (signInForm) {
        signInForm.addEventListener("submit", function (e) {
            var login = document.getElementById("glLogin").value.trim();
            var password = document.getElementById("glPassword").value;
            var error = document.getElementById("glError");

            if (!login || !password) {
                e.preventDefault();
                error.textContent = "Please enter both login and password.";
                error.classList.add("show");
                return;
            }
            error.classList.remove("show");
        });
    }
}

