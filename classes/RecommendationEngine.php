<?php
/**
 * Recommendation Engine
 * Dynamically synthesizes personalized, actionable recommendations
 * based on the student's identified weaknesses and scoring profile.
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AnalysisEngine.php';

class RecommendationEngine {
    private PDO $db;
    private AnalysisEngine $analysisEngine;

    public function __construct() {
        $this->db = getDB();
        $this->analysisEngine = new AnalysisEngine();
    }

    /**
     * Generate dynamic recommendations for a student and persist them
     */
    public function generateStudentRecommendations(int $studentId): array {
        $scores = $this->analysisEngine->calculateStudentAnalysis($studentId);

        // Fetch student records
        $stmtA = $this->db->prepare("SELECT * FROM academic_records WHERE student_id = ?");
        $stmtA->execute([$studentId]);
        $academic = $stmtA->fetch() ?: [];

        $stmtP = $this->db->prepare("SELECT count(*) FROM projects WHERE student_id = ?");
        $stmtP->execute([$studentId]);
        $projectCount = (int) $stmtP->fetchColumn();

        $stmtC = $this->db->prepare("SELECT count(*) FROM certifications WHERE student_id = ?");
        $stmtC->execute([$studentId]);
        $certCount = (int) $stmtC->fetchColumn();

        $stmtI = $this->db->prepare("SELECT count(*) FROM internships WHERE student_id = ? AND has_internship = 1");
        $stmtI->execute([$studentId]);
        $internCount = (int) $stmtI->fetchColumn();

        $stmtS = $this->db->prepare("
            SELECT s.name, ss.rating 
            FROM student_skills ss
            JOIN skills s ON ss.skill_id = s.id
            WHERE ss.student_id = ? AND s.category = 'soft_skill'
        ");
        $stmtS->execute([$studentId]);
        $softSkills = $stmtS->fetchAll();

        $recommendations = [];

        // 1. Backlog remediation
        $backlogs = (int) ($academic['num_backlogs'] ?? 0);
        if ($backlogs > 0) {
            $recommendations[] = [
                'category'    => 'academic',
                'title'       => 'Backlog Remediation Strategy',
                'description' => "Prioritize clearing your $backlogs active backlog course(s) during the upcoming supplementary examination cycle. Most placement screening criteria mandate zero backlogs.",
                'priority'    => 'High'
            ];
        }

        // 2. Attendance Improvement
        $attendance = (float) ($academic['attendance_percentage'] ?? 0);
        if ($attendance < 75.0) {
            $recommendations[] = [
                'category'    => 'academic',
                'title'       => 'Improve Attendance to Above 75%',
                'description' => "Your attendance is currently " . number_format($attendance, 1) . "%. Maintain regular attendance across lectures and laboratory classes to avoid condonation risks and detention.",
                'priority'    => 'High'
            ];
        }

        // 3. CGPA Enhancement
        $cgpa = (float) ($academic['current_cgpa'] ?? 0);
        if ($cgpa < 7.0 && $cgpa > 0) {
            $recommendations[] = [
                'category'    => 'academic',
                'title'       => 'CGPA Improvement Plan',
                'description' => "Target a minimum SGPA of 7.5+ in the ongoing semester by focusing on internal assessments, assignment submissions, and guided peer-study groups.",
                'priority'    => 'High'
            ];
        }

        // 4. Technical Skill Development
        if ($scores['technical_score'] < 65.0) {
            $recommendations[] = [
                'category'    => 'technical',
                'title'       => 'Structured Programming & Data Structures Practice',
                'description' => "Dedicate 1 hour daily to practice Data Structures and Algorithms on platforms like LeetCode or HackerRank. Focus on Arrays, Linked Lists, Trees, and SQL query optimization.",
                'priority'    => 'High'
            ];
        }

        // 5. Internship Acquisition
        if ($internCount === 0) {
            $recommendations[] = [
                'category'    => 'internship',
                'title'       => 'Pursue Industry or Research Internship',
                'description' => "Real-world experience is a vital differentiator. Apply for 4-to-8 week internships via college placement cell, AICTE Internship portal, or platforms like Internshala and LinkedIn.",
                'priority'    => 'High'
            ];
        }

        // 6. Project Portfolio
        if ($projectCount < 2) {
            $recommendations[] = [
                'category'    => 'projects',
                'title'       => 'Build at Least 2 Domain Projects with GitHub Documentation',
                'description' => "Develop minimum 2 practical applications (e.g., full-stack web, machine learning, or IoT system). Create clean README documentation, architecture diagrams, and commit history on GitHub.",
                'priority'    => 'Medium'
            ];
        }

        // 7. Industry Certifications
        if ($certCount < 2) {
            $recommendations[] = [
                'category'    => 'certifications',
                'title'       => 'Earn Industry-Recognized Certifications',
                'description' => "Complete certified credentials from reputable portals such as NPTEL / Swayam, AWS Certified Cloud Practitioner, Microsoft Azure, Google Cloud, or Coursera.",
                'priority'    => 'Medium'
            ];
        }

        // 8. Soft Skills & Interview Communication
        if ($scores['soft_skills_score'] < 70.0) {
            $recommendations[] = [
                'category'    => 'soft_skills',
                'title'       => 'Strengthen Interview Communication & Group Discussions',
                'description' => "Participate in mock technical interviews, Toastmasters, and departmental seminar presentations. Practice explaining complex technical concepts clearly within 2-3 minutes.",
                'priority'    => 'Medium'
            ];
        }

        // 9. Advance to Premier Roles (for High Performers)
        if ($scores['overall_score'] >= 80.0) {
            $recommendations[] = [
                'category'    => 'career',
                'title'       => 'System Design & Competitive Coding for Tier-1 Opportunities',
                'description' => "Prepare for high-package product engineering roles by studying System Design concepts (caching, load balancing, microservices) and participating in Codeforces / LeetCode bi-weekly contests.",
                'priority'    => 'Low'
            ];
        }

        // Always ensure at least 2 recommendations
        if (empty($recommendations)) {
            $recommendations[] = [
                'category'    => 'career',
                'title'       => 'Network with Industry Alumni and Maintain Technical Edge',
                'description' => 'Connect with college alumni working in your target domain. Stay updated with contemporary cloud and AI toolchains.',
                'priority'    => 'Low'
            ];
        }

        // Save recommendations to database
        $this->saveRecommendations($studentId, $recommendations);

        return $recommendations;
    }

    private function saveRecommendations(int $studentId, array $recs): void {
        // Clear previous recommendations to keep current
        $stmtDel = $this->db->prepare("DELETE FROM recommendations WHERE student_id = ?");
        $stmtDel->execute([$studentId]);

        $stmtIns = $this->db->prepare("
            INSERT INTO recommendations (student_id, category, title, description, priority, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        foreach ($recs as $r) {
            $stmtIns->execute([
                $studentId,
                $r['category'],
                $r['title'],
                $r['description'],
                $r['priority']
            ]);
        }
    }
}
