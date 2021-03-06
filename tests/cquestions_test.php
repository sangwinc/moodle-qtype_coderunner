<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for coderunner C questions.
 * @group qtype_coderunner
 * Assumed to be run after python questions have been tested, so focuses
 * only on C-specific aspects.
 *
 * @package    qtype
 * @subpackage coderunner
 * @copyright  2012 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/coderunner/tests/coderunnertestcase.php');

/**
 * Unit tests for coderunner C questions
 */
class qtype_coderunner_c_questions_test extends qtype_coderunner_testcase {

    public function test_good_sqr_function() {
        $q = $this->make_question('sqrC');
        $response = array('answer' => "int sqr(int n) { return n * n;}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(1, $mark);
        $this->assertEquals(question_state::$gradedright, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(4, count($testoutcome->testresults));
        $this->assertTrue($testoutcome->all_correct());
    }

    public function test_compile_error() {
        $q = $this->make_question('sqrC');
        $response = array('answer' => "int sqr(int n) { return n * n; /* No closing brace */");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(0, $mark);
        $this->assertEquals(question_state::$gradedwrong, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertTrue($testoutcome->has_syntax_error());
        $this->assertEquals(0, count($testoutcome->testresults));
    }

    public function test_good_hello_world() {
        $q = $this->make_question('helloProgC');
        $response = array('answer' => "#include <stdio.h>\nint main() { printf(\"Hello ENCE260\\n\");return 0;}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(1, $mark);
        $this->assertEquals(question_state::$gradedright, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testoutcome->has_syntax_error());
        $this->assertEquals(1, count($testoutcome->testresults));
        $this->assertTrue($testoutcome->all_correct());
    }

    public function test_bad_hello_world() {
        $q = $this->make_question('helloProgC');
        $response = array('answer' => "#include <stdio.h>\nint main() { printf(\"Hello ENCE260!\\n\");return 0;}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(0, $mark);
        $this->assertEquals(question_state::$gradedwrong, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testoutcome->has_syntax_error());
        $this->assertEquals(1, count($testoutcome->testresults));
        $this->assertFalse($testoutcome->all_correct());
    }

    public function test_copy_stdinC() {
        $q = $this->make_question('copyStdinC');
        $response = array('answer' => "#include <stdio.h>\nint main() { char c;\nwhile((c = getchar()) != EOF) {\n putchar(c);\n}\nreturn 0;}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(1, $mark);
        $this->assertEquals(question_state::$gradedright, $grade);
        $this->assertFalse($testoutcome->has_syntax_error());
        $this->assertEquals(3, count($testoutcome->testresults));
        $this->assertTrue($testoutcome->all_correct());
    }

    public function test_C_func_with_side_effects() {
        // This used to test the c_function_side_effects question type, but
        // that's now defunct. The test is still vaguely useful, though, as
        // it tests that what looks like a global array in each test case
        // is actually local to each test within the same combined function.
        $q = $this->make_question('strToUpper');
        $response = array('answer' =>
"void strToUpper(char s[]) {
    int i = 0;
    while (s[i]) {
       s[i] = toupper(s[i]);
       i++;
    }
}
");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(1, $mark);
        $this->assertEquals(question_state::$gradedright, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testoutcome->has_syntax_error());
        $this->assertEquals(2, count($testoutcome->testresults));
        $this->assertTrue($testoutcome->all_correct());
    }

    public function test_runtime_error() {
        $q = $this->make_question('helloProgC');
        $response = array('answer' => "#include <stdio.h>\n#include <stdlib.h>\nint main() { char* p = NULL; *p = 10; return 0; }\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(0, $mark);
        $this->assertEquals(question_state::$gradedwrong, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testoutcome->has_syntax_error());
        $this->assertEquals(1, count($testoutcome->testresults));
        $this->assertFalse($testoutcome->all_correct());
        $this->assertTrue(strpos($testoutcome->testresults[0]->got, '***Runtime error***') === 0);
    }

    public function test_timelimit_exceeded() {
        $q = $this->make_question('helloProgC');
        $response = array('answer' => "#include <stdio.h>\nint main() { while(1) {};return 0;}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(0, $mark);
        $this->assertEquals(question_state::$gradedwrong, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testoutcome->has_syntax_error());
        $this->assertEquals(1, count($testoutcome->testresults));
        $this->assertFalse($testoutcome->all_correct());
        $this->assertEquals("***Time limit exceeded***\n", $testoutcome->testresults[0]->got);
    }

    public function test_outputlimit_exceeded() {
        $q = $this->make_question('helloProgC');
        $response = array('answer' => "#include <stdio.h>\nint main() { while(1) { printf(\"Hello\"); };return 0;}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(0, $mark);
        $this->assertEquals(question_state::$gradedwrong, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testoutcome->has_syntax_error());
        $this->assertEquals(1, count($testoutcome->testresults));
        $this->assertFalse($testoutcome->all_correct());
        $this->assertTrue(
            strpos($testoutcome->testresults[0]->got, "***Time limit exceeded***\n") !== false ||
            strpos($testoutcome->testresults[0]->got, "***Excessive output***\n") !== false
        ); 
    }

    public function test_missing_semicolon() {
        // Check that a missing semicolon in a simple printf test is reinsterted.
        // Check grading of a "write-a-function" question with multiple
        // test cases and a correct solution.
        $q = $this->make_question('sqrNoSemicolons');
        $response = array('answer' => "int sqr(int n) { return n * n;}\n");
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals(1, $mark);
        $this->assertEquals(question_state::$gradedright, $grade);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(4, count($testoutcome->testresults));
        $this->assertTrue($testoutcome->all_correct());
    }

    public function test_simple_fork_bomb() {
        // Check that sandbox can handle a fork-bomb.
        $q = $this->make_question('sqrC');
        $response = array('answer' =>
"#include <linux/unistd.h>
#include <unistd.h>
int sqr(int n) {
    if (n == 0) return 0;
    else {
        int i = 0;
        for (i = 0; i < 2000; i++)
            fork();
        return n * n;
    }
}");
        $q->sandboxparams = '{"numprocs": 1}';
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testoutcome = unserialize($cache['_testoutcome']);
        $this->assertTrue(
                strpos($testoutcome->testresults[1]->got,
                    "***Illegal function call***") !== false ||
                $grade == question_state::$gradedright
                );
    }
}
