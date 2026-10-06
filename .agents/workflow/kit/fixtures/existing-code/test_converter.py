import unittest

from converter import to_fahrenheit


class ConverterTest(unittest.TestCase):
    def test_freezing_and_boiling(self):
        self.assertEqual(to_fahrenheit(0), 32)
        self.assertEqual(to_fahrenheit(100), 212)


if __name__ == "__main__":
    unittest.main()
